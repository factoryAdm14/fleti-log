<?php

namespace Modules\FinanceManagement\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\FinanceManagement\Entities\WalletTransaction;
use Modules\FinanceManagement\Service\DriverWalletService;
use Modules\FinanceManagement\Service\Interfaces\DriverWithdrawServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;

class FinanceWalletController extends Controller
{
    public function __construct(
        private readonly DriverWalletService $driverWalletService,
        private readonly FinanceSettingServiceInterface $financeSettingService,
        private readonly DriverWithdrawServiceInterface $driverWithdrawService,
    ) {
    }

    public function show(): JsonResponse
    {
        $driverId = auth('api')->id();
        $wallet = $this->driverWalletService->ensureWallet($driverId);
        $settings = $this->financeSettingService->get();

        return response()->json(responseFormatter(DEFAULT_200, [
            'available_balance' => $wallet->available_balance,
            'pending_balance' => $wallet->pending_balance,
            'blocked_balance' => $wallet->blocked_balance,
            'total_received' => $wallet->total_received,
            'total_withdrawn' => $wallet->total_withdrawn,
            'withdrawable_balance' => $wallet->available_balance,
            'min_withdraw_amount' => $settings->min_withdraw_amount,
            'has_open_withdraw' => $this->driverWithdrawService->hasOpenFinanceWithdraw($driverId),
        ]));
    }

    public function transactions(Request $request): JsonResponse
    {
        $driverId = auth('api')->id();
        $wallet = $this->driverWalletService->ensureWallet($driverId);

        $limit = (int) $request->get('limit', 20);
        $offset = (int) $request->get('offset', 1);

        $query = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->orderByDesc('created_at');

        $total = $query->count();
        $items = $query->forPage($offset, $limit)->get();

        $payload = responseFormatter(DEFAULT_200, $items);
        $payload['total_size'] = $total;
        $payload['limit'] = $limit;
        $payload['offset'] = $offset;

        return response()->json($payload);
    }
}
