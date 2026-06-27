<?php

namespace Modules\FinanceManagement\Service;

use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\UserManagement\Entities\WithdrawRequest;

class FinanceWithdrawSecurityService
{
    public function __construct(
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly FinanceAuditService $financeAuditService,
    ) {
    }

    public function validateWithdrawRequest(string $driverId, float $amount): void
    {
        $settings = $this->financeSettingService->get();

        if (!$settings->withdraw_security_enabled) {
            return;
        }

        if ((float) $settings->max_withdraw_amount > 0 && $amount > (float) $settings->max_withdraw_amount) {
            $this->logBlockedAttempt($driverId, $amount, 'max_withdraw_amount_exceeded');

            throw new FinanceWithdrawException(
                responseCode: 'withdraw_amount_limit_403',
                message: 'Valor excede o limite máximo permitido por saque.',
            );
        }

        $todayQuery = WithdrawRequest::query()
            ->where('user_id', $driverId)
            ->where('source', DriverWithdrawService::SOURCE_FINANCE)
            ->where('created_at', '>=', now()->startOfDay());

        $requestsToday = (int) (clone $todayQuery)->count();
        $amountToday = (float) (clone $todayQuery)->sum('amount');

        if ((int) $settings->max_withdraw_requests_per_day > 0
            && $requestsToday >= (int) $settings->max_withdraw_requests_per_day) {
            $this->logBlockedAttempt($driverId, $amount, 'max_withdraw_requests_per_day_exceeded');

            throw new FinanceWithdrawException(
                responseCode: 'withdraw_daily_limit_403',
                message: 'Limite diário de solicitações de saque atingido.',
            );
        }

        if ((float) $settings->max_withdraw_amount_per_day > 0
            && ($amountToday + $amount) > (float) $settings->max_withdraw_amount_per_day) {
            $this->logBlockedAttempt($driverId, $amount, 'max_withdraw_amount_per_day_exceeded');

            throw new FinanceWithdrawException(
                responseCode: 'withdraw_daily_amount_limit_403',
                message: 'Limite diário de valor para saque atingido.',
            );
        }
    }

    private function logBlockedAttempt(string $driverId, float $amount, string $reason): void
    {
        $this->financeAuditService->log(
            action: 'withdraw_blocked_suspicious',
            entityType: WithdrawRequest::class,
            entityId: null,
            after: [
                'driver_id' => $driverId,
                'amount' => $amount,
                'reason' => $reason,
            ],
            notes: 'Tentativa de saque bloqueada por regra de segurança',
        );
    }
}
