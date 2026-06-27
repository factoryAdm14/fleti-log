<?php

namespace Modules\FinanceManagement\Service;

use Carbon\Carbon;
use Modules\FinanceManagement\Entities\DriverPlan;
use Modules\FinanceManagement\Entities\DriverSubscription;
use Modules\FinanceManagement\Entities\DriverWallet;
use Modules\FinanceManagement\Entities\PaymentSplit;
use Modules\FinanceManagement\Entities\WalletTransaction;
use Modules\FinanceManagement\Service\Interfaces\FinanceDashboardServiceInterface;
use Modules\Gateways\Entities\PaymentRequest;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\WithdrawRequest;

class FinanceDashboardService implements FinanceDashboardServiceInterface
{
    public function getStats(string $period = 'all'): array
    {
        [$from, $to, $periodLabel] = $this->resolvePeriod($period);

        $splitQuery = PaymentSplit::query()->where('status', 'confirmed');
        $paidPaymentQuery = PaymentRequest::query()->where('is_paid', true);
        $withdrawBase = WithdrawRequest::query()->where('source', DriverWithdrawService::SOURCE_FINANCE);

        if ($from && $to) {
            $splitQuery->whereBetween('created_at', [$from, $to]);
            $paidPaymentQuery->whereBetween('updated_at', [$from, $to]);
            $withdrawBase->whereBetween('created_at', [$from, $to]);
        }

        $commissionRevenue = (float) (clone $splitQuery)->sum('admin_amount');
        $driverPaid = (float) (clone $splitQuery)->sum('driver_amount');
        $gatewayFees = (float) (clone $splitQuery)->sum('gateway_fee');
        $grossRideRevenue = (float) (clone $splitQuery)->sum('gross_amount');

        $planRevenue = (float) (clone $paidPaymentQuery)
            ->where('attribute', 'driver_subscription')
            ->sum('payment_amount');

        $pixReceived = (float) (clone $paidPaymentQuery)
            ->whereIn('payment_method', ['mercadopago_pix', 'efi_pix'])
            ->sum('payment_amount');

        $cardReceived = (float) (clone $paidPaymentQuery)
            ->where('payment_method', 'mercadopago')
            ->sum('payment_amount');

        $totalRevenue = $grossRideRevenue + $planRevenue;
        $estimatedNetProfit = $commissionRevenue + $planRevenue - $gatewayFees;

        $pendingWithdraws = $this->withdrawStats($withdrawBase, PENDING);
        $approvedWithdraws = $this->withdrawStats($withdrawBase, APPROVED);
        $settledWithdraws = $this->withdrawStats($withdrawBase, SETTLED);

        $driversWithActivePlan = (int) DriverSubscription::query()
            ->where('status', DriverSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>', Carbon::now())
            ->distinct()
            ->count('driver_id');

        $totalActiveDrivers = (int) User::query()
            ->where('user_type', 'driver')
            ->where('is_active', 1)
            ->count();

        return [
            'period' => $period,
            'period_label' => $periodLabel,
            'total_revenue' => round($totalRevenue, 2),
            'commission_revenue' => round($commissionRevenue, 2),
            'plan_revenue' => round($planRevenue, 2),
            'driver_paid' => round($driverPaid, 2),
            'gateway_fees' => round($gatewayFees, 2),
            'estimated_net_profit' => round($estimatedNetProfit, 2),
            'pix_received' => round($pixReceived, 2),
            'card_received' => round($cardReceived, 2),
            'pending_withdraws_count' => $pendingWithdraws['count'],
            'pending_withdraws_amount' => $pendingWithdraws['amount'],
            'approved_withdraws_count' => $approvedWithdraws['count'],
            'approved_withdraws_amount' => $approvedWithdraws['amount'],
            'settled_withdraws_count' => $settledWithdraws['count'],
            'settled_withdraws_amount' => $settledWithdraws['amount'],
            'drivers_with_active_plan' => $driversWithActivePlan,
            'drivers_commission_mode' => max(0, $totalActiveDrivers - $driversWithActivePlan),
            'wallet_available' => round((float) DriverWallet::query()->sum('available_balance'), 2),
            'wallet_pending' => round((float) DriverWallet::query()->sum('pending_balance'), 2),
            'wallet_blocked' => round((float) DriverWallet::query()->sum('blocked_balance'), 2),
            'active_subscriptions' => (int) DriverSubscription::query()
                ->where('status', DriverSubscription::STATUS_ACTIVE)
                ->where('expires_at', '>', Carbon::now())
                ->count(),
            'transactions_count' => $this->countWalletTransactions($from, $to),
            'plans_count' => (int) DriverPlan::query()->where('is_active', true)->count(),
        ];
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    private function resolvePeriod(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay(), 'Hoje'],
            'week' => [now()->subDays(6)->startOfDay(), now()->endOfDay(), 'Últimos 7 dias'],
            'month' => [now()->startOfMonth(), now()->endOfMonth(), 'Este mês'],
            'year' => [now()->startOfYear(), now()->endOfYear(), 'Este ano'],
            default => [null, null, 'Todo o período'],
        };
    }

    /**
     * @return array{count: int, amount: float}
     */
    private function withdrawStats($baseQuery, string $status): array
    {
        $query = (clone $baseQuery)->where('status', $status);

        return [
            'count' => (int) (clone $query)->count(),
            'amount' => round((float) (clone $query)->sum('amount'), 2),
        ];
    }

    private function countWalletTransactions(?Carbon $from, ?Carbon $to): int
    {
        $query = WalletTransaction::query();

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return (int) $query->count();
    }
}
