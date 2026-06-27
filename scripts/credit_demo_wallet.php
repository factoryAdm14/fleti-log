#!/usr/bin/env php
<?php
/**
 * Credit demo customer wallet (user_accounts) and driver finance wallet for E2E tests.
 *
 * Usage:
 *   php scripts/credit_demo_wallet.php
 *   php scripts/credit_demo_wallet.php --customer-phone=+5544999000001 --customer-amount=100
 *   php scripts/credit_demo_wallet.php --driver-phone=+5544999000002 --driver-amount=50
 */

$adminRoot = dirname(__DIR__);
$localAdmin = $adminRoot . '/fleti-admin-new-install-3.2';
if (is_dir($localAdmin . '/vendor')) {
    $adminRoot = $localAdmin;
} elseif (!is_dir($adminRoot . '/vendor')) {
    fwrite(STDERR, "Laravel root not found\n");
    exit(1);
}

chdir($adminRoot);
require $adminRoot . '/vendor/autoload.php';
$app = require $adminRoot . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Modules\FinanceManagement\Entities\DriverWallet;
use Modules\FinanceManagement\Entities\WalletTransaction;
use Modules\FinanceManagement\Service\DriverWalletService;
use Modules\UserManagement\Entities\User;

$options = getopt('', [
    'customer-phone:',
    'customer-amount:',
    'driver-phone:',
    'driver-amount:',
]);

$customerPhone = $options['customer-phone'] ?? '+5544999000001';
$customerAmount = (float) ($options['customer-amount'] ?? 100);
$driverPhone = $options['driver-phone'] ?? '+5544999000002';
$driverAmount = (float) ($options['driver-amount'] ?? 50);

$result = ['ok' => true, 'customer' => null, 'driver' => null];

try {
    DB::beginTransaction();

    $customer = User::query()->where('phone', $customerPhone)->where('user_type', CUSTOMER)->first();
    if ($customer && $customerAmount > 0) {
        $customer->userAccount()->firstOrCreate(['user_id' => $customer->id]);
        $customer->userAccount()->increment('wallet_balance', $customerAmount);
        $customer->refresh();
        $result['customer'] = [
            'id' => $customer->id,
            'phone' => $customer->phone,
            'credited' => $customerAmount,
            'wallet_balance' => (float) $customer->userAccount->wallet_balance,
        ];
    }

    $driver = User::query()->where('phone', $driverPhone)->where('user_type', DRIVER)->first();
    if ($driver && $driverAmount > 0) {
        /** @var DriverWalletService $walletService */
        $walletService = app(DriverWalletService::class);
        $wallet = $walletService->ensureWallet($driver->id);
        $wallet->increment('available_balance', $driverAmount);
        $wallet->increment('total_received', $driverAmount);
        WalletTransaction::query()->create([
            'driver_id' => $driver->id,
            'wallet_id' => $wallet->id,
            'type' => WalletTransaction::TYPE_ADJUSTMENT,
            'amount' => $driverAmount,
            'description' => 'Demo credit (E2E test)',
            'status' => 'completed',
            'reference' => 'demo_credit_' . now()->format('YmdHis'),
        ]);
        $wallet->refresh();
        $result['driver'] = [
            'id' => $driver->id,
            'phone' => $driver->phone,
            'credited' => $driverAmount,
            'available_balance' => (float) $wallet->available_balance,
        ];
    }

    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    $result = ['ok' => false, 'error' => $e->getMessage()];
}

echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
exit($result['ok'] ? 0 : 1);
