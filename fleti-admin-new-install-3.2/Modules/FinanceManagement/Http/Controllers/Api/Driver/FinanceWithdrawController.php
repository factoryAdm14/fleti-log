<?php

namespace Modules\FinanceManagement\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\FinanceManagement\Exceptions\FinanceWithdrawException;
use Modules\FinanceManagement\Http\Requests\FinanceWithdrawRequest;
use Modules\FinanceManagement\Http\Resources\FinanceWithdrawResource;
use Modules\FinanceManagement\Service\Interfaces\DriverWithdrawServiceInterface;
use Modules\FinanceManagement\Service\Interfaces\FinanceSettingServiceInterface;
use Modules\UserManagement\Entities\UserWithdrawMethodInfo;

class FinanceWithdrawController extends Controller
{
    public function __construct(
        private readonly DriverWithdrawServiceInterface $driverWithdrawService,
        private readonly FinanceSettingServiceInterface $financeSettingService,
    ) {
    }

    public function request(FinanceWithdrawRequest $request): JsonResponse
    {
        try {
            $driverId = auth('api')->id();
            [$methodId, $methodFields] = $this->resolveWithdrawMethodData($request, $driverId);

            $withdrawRequest = $this->driverWithdrawService->requestWithdraw(
                driverId: $driverId,
                amount: (float) $request->amount,
                withdrawMethodId: $methodId,
                methodFields: $methodFields,
                driverNote: $request->note,
            );

            return response()->json(responseFormatter(WITHDRAW_REQUEST_200, [
                'withdraw_request' => FinanceWithdrawResource::make($withdrawRequest),
            ]));
        } catch (FinanceWithdrawException $e) {
            return response()->json(responseFormatter([
                'response_code' => $e->responseCode,
                'message' => $e->getMessage(),
            ]), $e->httpStatus);
        }
    }

    public function pending(Request $request): JsonResponse
    {
        return $this->listWithdraws($request, [PENDING, APPROVED, DENIED]);
    }

    public function settled(Request $request): JsonResponse
    {
        return $this->listWithdraws($request, [SETTLED]);
    }

    private function listWithdraws(Request $request, array $statuses): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|integer|min:1',
            'offset' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(DEFAULT_400, errors: errorProcessor($validator)), 400);
        }

        $driverId = auth('api')->id();
        $limit = (int) $request->limit;
        $offset = (int) $request->offset;

        $items = $this->driverWithdrawService->listByDriver($driverId, $statuses, $limit, $offset);
        $total = $this->driverWithdrawService->countByDriver($driverId, $statuses);

        $payload = responseFormatter(DEFAULT_200, FinanceWithdrawResource::collection($items));
        $payload['total_size'] = $total;
        $payload['limit'] = $limit;
        $payload['offset'] = $offset;

        return response()->json($payload);
    }

    /**
     * @return array{0: int, 1: array<string, mixed>}
     */
    private function resolveWithdrawMethodData(FinanceWithdrawRequest $request, string $driverId): array
    {
        if ($request->filled('withdraw_method_info_id')) {
            $info = UserWithdrawMethodInfo::query()
                ->where('user_id', $driverId)
                ->where('is_active', 1)
                ->findOrFail($request->withdraw_method_info_id);

            return [(int) $info->withdraw_method_id, $info->method_info ?? []];
        }

        $methodId = (int) $request->withdraw_method;
        $reserved = ['amount', 'withdraw_method', 'withdraw_method_info_id', 'note'];
        $methodFields = collect($request->except($reserved))->all();

        return [$methodId, $methodFields];
    }
}
