<?php

namespace Modules\FinanceManagement\Service\Interfaces;

use Modules\UserManagement\Entities\WithdrawRequest;

interface DriverWithdrawServiceInterface
{
    /**
     * @param  array<string, mixed>  $methodFields
     */
    public function requestWithdraw(
        string $driverId,
        float $amount,
        int $withdrawMethodId,
        array $methodFields,
        ?string $driverNote = null,
    ): WithdrawRequest;

    public function hasOpenFinanceWithdraw(string $driverId): bool;

    /**
     * @return \Illuminate\Support\Collection<int, WithdrawRequest>
     */
    public function listByDriver(string $driverId, array $statuses, int $limit, int $offset): \Illuminate\Support\Collection;

    public function countByDriver(string $driverId, array $statuses): int;
}
