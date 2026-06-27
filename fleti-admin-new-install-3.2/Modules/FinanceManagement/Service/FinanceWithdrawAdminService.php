<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\FinanceManagement\Entities\DriverWallet;
use Modules\FinanceManagement\Entities\WalletTransaction;
use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\FinanceManagement\Service\Interfaces\FinanceWithdrawAdminServiceInterface;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\WithdrawRequest;

class FinanceWithdrawAdminService implements FinanceWithdrawAdminServiceInterface
{
    public function __construct(
        private readonly FinancePixPayoutService $financePixPayoutService,
    ) {
    }

    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = WithdrawRequest::query()
            ->with(['user', 'method'])
            ->where('source', DriverWithdrawService::SOURCE_FINANCE)
            ->orderByDesc('created_at');

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findFinanceWithdraw(int|string $id): WithdrawRequest
    {
        $withdraw = WithdrawRequest::query()
            ->with(['user', 'method'])
            ->where('source', DriverWithdrawService::SOURCE_FINANCE)
            ->find($id);

        if (!$withdraw) {
            throw new FinanceWithdrawException(
                responseCode: 'withdraw_not_found_404',
                message: 'Solicitação de saque não encontrada.',
                httpStatus: 404,
            );
        }

        return $withdraw;
    }

    public function approve(int|string $id, string $adminId, ?string $approvalNote = null): WithdrawRequest
    {
        $withdraw = DB::transaction(function () use ($id, $adminId, $approvalNote) {
            $withdraw = $this->lockFinanceWithdraw($id);

            if ($withdraw->status !== PENDING) {
                throw new FinanceWithdrawException(
                    responseCode: 'withdraw_invalid_status_403',
                    message: 'Apenas saques pendentes podem ser aprovados.',
                );
            }

            $withdraw->update([
                'status' => APPROVED,
                'approval_note' => $approvalNote,
                'admin_id' => $adminId,
                'under_review_at' => now(),
            ]);

            $this->syncWalletTransactionStatus($withdraw, 'approved');
            $this->auditAdminAction('withdraw_approved', $withdraw, $adminId);
            $this->notifyDriver($withdraw, APPROVED);

            return $withdraw->fresh(['user', 'method']);
        });

        if ($this->financePixPayoutService->isAutoPayoutEnabled()) {
            return $this->processAutoPixPayout($withdraw, $adminId);
        }

        return $withdraw;
    }

    public function retryPixPayout(int|string $id, string $adminId): WithdrawRequest
    {
        $withdraw = $this->findFinanceWithdraw($id);

        if ($withdraw->status !== APPROVED) {
            throw new FinanceWithdrawException(
                responseCode: 'withdraw_invalid_status_403',
                message: 'PIX automático só pode ser reenviado para saques aprovados.',
            );
        }

        return $this->processAutoPixPayout($withdraw, $adminId);
    }

    private function processAutoPixPayout(WithdrawRequest $withdraw, string $adminId): WithdrawRequest
    {
        $result = $this->financePixPayoutService->attemptPayout($withdraw->fresh());

        if ($result->success && $result->status !== 'already_sent') {
            return $this->settle(
                id: $withdraw->id,
                adminId: $adminId,
                existingReceiptUrl: $result->endToEndId
                    ? 'pix-e2e:' . $result->endToEndId
                    : ($result->reference ? 'pix-ref:' . $result->reference : null),
            );
        }

        return $withdraw->fresh(['user', 'method']);
    }

    public function deny(int|string $id, string $adminId, ?string $deniedNote = null): WithdrawRequest
    {
        return DB::transaction(function () use ($id, $adminId, $deniedNote) {
            $withdraw = $this->lockFinanceWithdraw($id);

            if ($withdraw->status !== PENDING) {
                throw new FinanceWithdrawException(
                    responseCode: 'withdraw_invalid_status_403',
                    message: 'Apenas saques pendentes podem ser recusados.',
                );
            }

            $this->releaseBlockedFunds($withdraw);

            $withdraw->update([
                'status' => DENIED,
                'denied_note' => $deniedNote,
                'admin_id' => $adminId,
            ]);

            $this->syncWalletTransactionStatus($withdraw, 'cancelled');
            $this->auditAdminAction('withdraw_denied', $withdraw, $adminId);
            $this->notifyDriver($withdraw, DENIED);

            return $withdraw->fresh(['user', 'method']);
        });
    }

    public function settle(
        int|string $id,
        string $adminId,
        ?UploadedFile $receipt = null,
        ?string $existingReceiptUrl = null,
    ): WithdrawRequest {
        return DB::transaction(function () use ($id, $adminId, $receipt, $existingReceiptUrl) {
            $withdraw = $this->lockFinanceWithdraw($id);

            if ($withdraw->status !== APPROVED) {
                throw new FinanceWithdrawException(
                    responseCode: 'withdraw_invalid_status_403',
                    message: 'Apenas saques aprovados podem ser marcados como pagos.',
                );
            }

            $receiptPath = $this->storeReceipt($receipt) ?? $existingReceiptUrl;

            $this->finalizeBlockedFunds($withdraw);

            $withdraw->update([
                'status' => SETTLED,
                'admin_id' => $adminId,
                'receipt_url' => $receiptPath,
                'paid_at' => now(),
            ]);

            $this->syncWalletTransactionStatus($withdraw, 'completed');
            $this->auditAdminAction('withdraw_settled', $withdraw, $adminId, [
                'receipt_url' => $receiptPath,
            ]);
            $this->notifyDriver($withdraw, SETTLED);

            return $withdraw->fresh(['user', 'method']);
        });
    }

    private function lockFinanceWithdraw(int|string $id): WithdrawRequest
    {
        $withdraw = WithdrawRequest::query()
            ->where('source', DriverWithdrawService::SOURCE_FINANCE)
            ->lockForUpdate()
            ->find($id);

        if (!$withdraw) {
            throw new FinanceWithdrawException(
                responseCode: 'withdraw_not_found_404',
                message: 'Solicitação de saque não encontrada.',
                httpStatus: 404,
            );
        }

        return $withdraw;
    }

    private function walletForWithdraw(WithdrawRequest $withdraw): DriverWallet
    {
        $wallet = DriverWallet::query()
            ->where('driver_id', $withdraw->user_id)
            ->lockForUpdate()
            ->first();

        if (!$wallet) {
            throw new FinanceWithdrawException(
                responseCode: 'wallet_not_found_404',
                message: 'Carteira do motorista não encontrada.',
                httpStatus: 404,
            );
        }

        return $wallet;
    }

    private function releaseBlockedFunds(WithdrawRequest $withdraw): void
    {
        $wallet = $this->walletForWithdraw($withdraw);
        $amount = (float) $withdraw->amount;

        if ((float) $wallet->blocked_balance < $amount) {
            throw new FinanceWithdrawException(
                responseCode: 'wallet_blocked_insufficient_403',
                message: 'Saldo bloqueado insuficiente para estornar o saque.',
            );
        }

        $wallet->blocked_balance = round((float) $wallet->blocked_balance - $amount, 2);
        $wallet->available_balance = round((float) $wallet->available_balance + $amount, 2);
        $wallet->save();
    }

    private function finalizeBlockedFunds(WithdrawRequest $withdraw): void
    {
        $wallet = $this->walletForWithdraw($withdraw);
        $amount = (float) $withdraw->amount;

        if ((float) $wallet->blocked_balance < $amount) {
            throw new FinanceWithdrawException(
                responseCode: 'wallet_blocked_insufficient_403',
                message: 'Saldo bloqueado insuficiente para liquidar o saque.',
            );
        }

        $wallet->blocked_balance = round((float) $wallet->blocked_balance - $amount, 2);
        $wallet->total_withdrawn = round((float) $wallet->total_withdrawn + $amount, 2);
        $wallet->save();
    }

    private function syncWalletTransactionStatus(WithdrawRequest $withdraw, string $status): void
    {
        if (!$withdraw->wallet_transaction_id) {
            return;
        }

        WalletTransaction::query()
            ->where('id', $withdraw->wallet_transaction_id)
            ->update(['status' => $status]);
    }

    private function storeReceipt(?UploadedFile $receipt): ?string
    {
        if (!$receipt) {
            return null;
        }

        $fileName = fileUploader('finance/withdraw-receipts/', 'pdf', $receipt);

        return $fileName ? 'finance/withdraw-receipts/' . $fileName : null;
    }

    private function auditAdminAction(string $action, WithdrawRequest $withdraw, string $adminId, array $extra = []): void
    {
        app(FinanceAuditService::class)->log(
            action: $action,
            entityType: WithdrawRequest::class,
            entityId: (string) $withdraw->id,
            after: array_merge([
                'withdraw_id' => $withdraw->id,
                'driver_id' => $withdraw->user_id,
                'amount' => $withdraw->amount,
                'status' => $withdraw->status,
                'admin_id' => $adminId,
            ], $extra),
        );
    }

    private function notifyDriver(WithdrawRequest $withdraw, string $status): void
    {
        $user = $withdraw->user ?? User::query()->find($withdraw->user_id);
        if (!$user?->fcm_token) {
            return;
        }

        $notificationKey = match ($status) {
            APPROVED => 'withdraw_request_approved',
            DENIED => 'withdraw_request_rejected',
            SETTLED => 'withdraw_request_settled',
            default => null,
        };

        if (!$notificationKey) {
            return;
        }

        $push = getNotification($notificationKey);
        if (empty($push)) {
            return;
        }

        sendDeviceNotification(
            fcm_token: $user->fcm_token,
            title: translate(key: $push['title'], locale: $user->current_language_key),
            description: textVariableDataFormat(
                value: $push['description'],
                userName: $user->first_name . ' ' . $user->last_name,
                withdrawNote: $withdraw->denied_note,
                locale: $user->current_language_key,
            ),
            status: $push['status'],
            notification_type: 'withdraw_request',
            action: $push['action'],
            user_id: $user->id,
        );
    }
}
