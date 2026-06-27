<?php

namespace Modules\FinanceManagement\Service\Interfaces;

interface FinanceDashboardServiceInterface
{
    /**
     * @return array{
     *     period: string,
     *     period_label: string,
     *     total_revenue: float,
     *     commission_revenue: float,
     *     plan_revenue: float,
     *     driver_paid: float,
     *     gateway_fees: float,
     *     estimated_net_profit: float,
     *     pix_received: float,
     *     card_received: float,
     *     pending_withdraws_count: int,
     *     pending_withdraws_amount: float,
     *     approved_withdraws_count: int,
     *     approved_withdraws_amount: float,
     *     settled_withdraws_count: int,
     *     settled_withdraws_amount: float,
     *     drivers_with_active_plan: int,
     *     drivers_commission_mode: int,
     *     wallet_available: float,
     *     wallet_pending: float,
     *     wallet_blocked: float,
     *     active_subscriptions: int,
     *     transactions_count: int,
     *     plans_count: int,
     * }
     */
    public function getStats(string $period = 'all'): array;
}
