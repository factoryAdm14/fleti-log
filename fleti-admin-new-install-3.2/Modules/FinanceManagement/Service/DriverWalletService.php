<?php

namespace Modules\FinanceManagement\Service;

use Modules\FinanceManagement\Entities\DriverSubscription;
use Modules\FinanceManagement\Entities\DriverWallet;

class DriverWalletService
{
    public function ensureWallet(string $driverId): DriverWallet
    {
        return DriverWallet::query()->firstOrCreate(
            ['driver_id' => $driverId],
            [
                'available_balance' => 0,
                'pending_balance' => 0,
                'blocked_balance' => 0,
                'total_received' => 0,
                'total_withdrawn' => 0,
            ]
        );
    }

    public function getWallet(string $driverId): ?DriverWallet
    {
        return DriverWallet::query()->where('driver_id', $driverId)->first();
    }
}
