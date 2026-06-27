<?php

namespace Modules\FinanceManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\FinanceManagement\Http\Requests\FinanceWithdrawActionRequest;
use Modules\FinanceManagement\Service\DriverWithdrawService;
use Modules\FinanceManagement\Service\Interfaces\FinanceWithdrawAdminServiceInterface;
use Modules\UserManagement\Entities\WithdrawRequest;

class FinanceWithdrawAdminController extends Controller
{
    public function __construct(
        private readonly FinanceWithdrawAdminServiceInterface $withdrawAdminService,
    ) {
    }

    public function index(): View
    {
        $status = request('status', 'all');
        $requests = $this->withdrawAdminService->paginateForAdmin([
            'status' => $status,
            'search' => request('search'),
        ], paginationLimit());

        $counts = [
            'pending' => WithdrawRequest::query()
                ->where('source', DriverWithdrawService::SOURCE_FINANCE)->where('status', PENDING)->count(),
            'approved' => WithdrawRequest::query()
                ->where('source', DriverWithdrawService::SOURCE_FINANCE)->where('status', APPROVED)->count(),
            'settled' => WithdrawRequest::query()
                ->where('source', DriverWithdrawService::SOURCE_FINANCE)->where('status', SETTLED)->count(),
        ];

        return view('financemanagement::admin.withdraws.index', compact('requests', 'status', 'counts'));
    }

    public function action(int|string $id, FinanceWithdrawActionRequest $request): RedirectResponse
    {
        $adminId = (string) auth()->id();

        try {
            $withdraw = match ($request->status) {
                APPROVED => $this->withdrawAdminService->approve($id, $adminId, $request->approval_note),
                DENIED => $this->withdrawAdminService->deny($id, $adminId, $request->denied_note),
                SETTLED => $this->withdrawAdminService->settle($id, $adminId, $request->file('receipt')),
                default => throw new FinanceWithdrawException('invalid_status', 'Ação inválida.'),
            };

            if ($request->status === APPROVED && $withdraw->status === APPROVED && $withdraw->pix_payout_status === 'failed') {
                Toastr::warning('Saque aprovado, mas o PIX automático falhou. Reenvie ou liquide manualmente.');
            } elseif ($request->status === APPROVED && $withdraw->status === SETTLED) {
                Toastr::success('Saque aprovado e pago via PIX automaticamente.');
            } else {
                Toastr::success(translate('Withdraw request updated successfully.'));
            }
        } catch (FinanceWithdrawException $e) {
            Toastr::error($e->getMessage());
        }

        return redirect()->route('admin.finance.withdraws.index', request()->only('status', 'search'));
    }

    public function retryPix(int|string $id): RedirectResponse
    {
        $adminId = (string) auth()->id();

        try {
            $this->withdrawAdminService->retryPixPayout($id, $adminId);
            Toastr::success('PIX reenviado. Verifique o status do saque.');
        } catch (FinanceWithdrawException $e) {
            Toastr::error($e->getMessage());
        }

        return redirect()->route('admin.finance.withdraws.index', request()->only('status', 'search'));
    }
}
