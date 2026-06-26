<?php

namespace Modules\ZoneManagement\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZoneManagement\Service\Interfaces\ZoneServiceInterface;
use Modules\ZoneManagement\Transformers\ZoneResource;

class ZoneController extends Controller
{
    protected $zoneService;
    public function __construct(ZoneServiceInterface $zoneService)
    {
        $this->zoneService = $zoneService;
    }
    public function list(Request $request): JsonResponse
    {
        $criteria['is_active'] =  1;
        $limit = min(max((int)($request->input('limit', DEFAULT_PAGINATION)), 1), 100);
        $offset = max((int)($request->input('offset', 1)), 1);
        $zones = $this->zoneService->getBy(criteria: $criteria, orderBy: ['created_at' => 'desc'], limit: $limit, offset: $offset);
        $zoneList = ZoneResource::collection($zones);
        return response()->json(responseFormatter(constant: DEFAULT_200, content: $zoneList, limit: $limit, offset: $offset), 200);
    }
}
