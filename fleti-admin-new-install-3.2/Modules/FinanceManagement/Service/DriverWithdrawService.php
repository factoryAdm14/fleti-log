<?php

namespace Modules\FinanceManagement\Service;

use Illuminate\Support\Facades\DB;
use Modules\FinanceManagement\Entities\DriverWallet;
use Modules\FinanceManagement\Entities\WalletTransaction;
use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\FinanceManagement\Service\Interfaces\DriverWithdrawServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\UserManagement\Entities\WithdrawMethod;
use Modules\UserManagement\Entities\WithdrawRequest;

class DriverWithdrawService implements DriverWithdrawServiceInterface
{
    public const SOURCE_FINANCE = 'finance';

    public function __construct(
        private readonly DriverWalletService $driverWalletService,
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly FinanceAuditService $financeAuditService,
        private readonly FinanceWithdrawSecurityService $withdrawSecurityService,
    ) {
    }

    public function requestWithdraw(
        string $driverId,
        float $amount,
        int $withdrawMethodId,
        array $methodFields,
        ?string $driverNote = null,
    ): WithdrawRequest {
        $amount = round($amount, 2);
        $settings = $this->financeSettingService->get();

        if ($amount < (float) $settings->min_withdraw_amount) {
            throw new FinanceWithdrawException(
                responseCode: 'withdraw_min_amount_403',
                message: 'Valor abaixo do mínimo permitido para saque.',
            );
        }

        if ($this->hasOpenFinanceWithdraw($driverId)) {
            throw new FinanceWithdrawException(
                responseCode: 'withdraw_duplicate_403',
                message: 'Já existe uma solicitação de saque em aberto.',
            );
        }

        $this->withdrawSecurityService->validateWithdrawRequest($driverId, $amount);

        $method = WithdrawMethod::query()
            ->where('id', $withdrawMethodId)
            ->where('is_active', 1)
            ->first();

        if (!$method) {
            throw new FinanceWithdrawException(
                responseCode: 'withdraw_method_404',
                message: 'Método de saque inválido.',
                httpStatus: 404,
            );
        }

        $resolvedFields = $this->resolveMethodFields($method, $methodFields);

        return DB::transaction(function () use ($driverId, $amount, $withdrawMethodId, $resolvedFields, $driverNote) {
            $wallet = DriverWallet::query()
                ->where('driver_id', $driverId)
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = $this->driverWalletService->ensureWallet($driverId);
                $wallet = DriverWallet::query()->where('id', $wallet->id)->lockForUpdate()->first();
            }

            if ((float) $wallet->available_balance < $amount) {
                throw new FinanceWithdrawException(
                    responseCode: 'insufficient_fund_403',
                    message: 'Saldo disponível insuficiente para saque.',
                );
            }

            $wallet->available_balance = round((float) $wallet->available_balance - $amount, 2);
            $wallet->blocked_balance = round((float) $wallet->blocked_balance + $amount, 2);
            $wallet->save();

            $withdrawRequest = WithdrawRequest::query()->create([
                'user_id' => $driverId,
                'amount' => $amount,
                'method_id' => $withdrawMethodId,
                'method_fields' => $resolvedFields,
                'driver_note' => $driverNote,
                'status' => PENDING,
                'source' => self::SOURCE_FINANCE,
            ]);

            $walletTransaction = WalletTransaction::query()->create([
                'driver_id' => $driverId,
                'wallet_id' => $wallet->id,
                'type' => WalletTransaction::TYPE_WITHDRAW,
                'amount' => $amount,
                'description' => 'Solicitação de saque #' . $withdrawRequest->id,
                'status' => 'pending',
                'reference' => (string) $withdrawRequest->id,
            ]);

            $withdrawRequest->update([
                'wallet_transaction_id' => $walletTransaction->id,
            ]);

            $this->financeAuditService->logWalletTransaction($walletTransaction, 'withdraw_requested');

            $this->financeAuditService->log(
                action: 'withdraw_requested',
                entityType: WithdrawRequest::class,
                entityId: (string) $withdrawRequest->id,
                after: [
                    'amount' => $amount,
                    'wallet_id' => $wallet->id,
                    'wallet_transaction_id' => $walletTransaction->id,
                    'available_balance' => $wallet->available_balance,
                    'blocked_balance' => $wallet->blocked_balance,
                ],
                notes: 'Saque solicitado pelo motorista',
            );

            return $withdrawRequest->fresh(['method']);
        });
    }

    public function hasOpenFinanceWithdraw(string $driverId): bool
    {
        return WithdrawRequest::query()
            ->where('user_id', $driverId)
            ->where('source', self::SOURCE_FINANCE)
            ->whereIn('status', [PENDING, APPROVED])
            ->exists();
    }

    public function listByDriver(string $driverId, array $statuses, int $limit, int $offset): \Illuminate\Support\Collection
    {
        return WithdrawRequest::query()
            ->with(['method'])
            ->where('user_id', $driverId)
            ->where('source', self::SOURCE_FINANCE)
            ->whereIn('status', $statuses)
            ->orderByDesc('created_at')
            ->forPage($offset, $limit)
            ->get();
    }

    public function countByDriver(string $driverId, array $statuses): int
    {
        return WithdrawRequest::query()
            ->where('user_id', $driverId)
            ->where('source', self::SOURCE_FINANCE)
            ->whereIn('status', $statuses)
            ->count();
    }

    /**
     * @param  array<string, mixed>  $submittedFields
     * @return array<string, mixed>
     */
    private function resolveMethodFields(WithdrawMethod $method, array $submittedFields): array
    {
        $expected = array_column($method->method_fields ?? [], 'input_name');
        $resolved = [];

        foreach ($expected as $field) {
            if (!array_key_exists($field, $submittedFields) || $submittedFields[$field] === null || $submittedFields[$field] === '') {
                throw new FinanceWithdrawException(
                    responseCode: 'withdraw_method_fields_400',
                    message: "Campo obrigatório ausente: {$field}",
                    httpStatus: 400,
                );
            }
            $resolved[$field] = $submittedFields[$field];
        }

        return $resolved;
    }
}
