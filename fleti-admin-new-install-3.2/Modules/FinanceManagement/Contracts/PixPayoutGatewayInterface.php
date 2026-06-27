<?php

namespace Modules\FinanceManagement\Contracts;

use Modules\FinanceManagement\DTO\PixPayoutResult;
use Modules\UserManagement\Entities\WithdrawRequest;

interface PixPayoutGatewayInterface
{
    public function key(): string;

    public function isAvailable(): bool;

    public function sendDriverPayout(WithdrawRequest $withdraw, string $pixKey): PixPayoutResult;
}
