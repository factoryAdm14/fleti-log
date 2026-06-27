<?php

namespace Modules\TripManagement\Http\Controllers\Api\Driver;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\TripManagement\Entities\TripStop;
use Modules\TripManagement\Service\Interfaces\TripRequestServiceInterface;
use Modules\TripManagement\Service\Interfaces\TripStopServiceInterface;
use Modules\TripManagement\Transformers\TripStopResource;

class TripStopController extends Controller
{
    public function __construct(
        private readonly TripStopServiceInterface $tripStopService,
        private readonly TripRequestServiceInterface $tripRequestService,
    ) {
    }

    public function index(string $tripRequestId): JsonResponse
    {
        $trip = $this->tripRequestService->findOneBy(criteria: ['id' => $tripRequestId]);
        if (!$trip || $trip->driver_id !== auth('api')->id()) {
            return response()->json(responseFormatter(TRIP_REQUEST_404), 403);
        }

        $stops = $this->tripStopService->listByTrip($tripRequestId);

        return response()->json(responseFormatter(DEFAULT_200, TripStopResource::collection($stops)));
    }

    public function arrive(Request $request, string $stopId): JsonResponse
    {
        $stop = $this->resolveDriverStop($stopId);
        if ($stop instanceof JsonResponse) {
            return $stop;
        }

        $stop = $this->tripStopService->markArrived($stop);

        return response()->json(responseFormatter(DEFAULT_UPDATE_200, TripStopResource::make($stop)));
    }

    public function complete(Request $request, string $stopId): JsonResponse
    {
        $stop = $this->resolveDriverStop($stopId);
        if ($stop instanceof JsonResponse) {
            return $stop;
        }

        $validator = Validator::make($request->all(), [
            'signature' => 'nullable|string',
            'qr_code' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
            'proof_photo' => 'nullable|image|max:' . convertBytesToKiloBytes(maxUploadSize('image')),
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 403);
        }

        $payload = $request->only(['signature', 'qr_code', 'notes']);

        if ($request->hasFile('proof_photo')) {
            $payload['proof_photo'] = fileUploader(
                'trip/parcel/proof/stop/',
                $request->file('proof_photo')->getClientOriginalExtension(),
                $request->file('proof_photo')
            );
        }

        $stop = $this->tripStopService->markCompleted($stop, $payload);

        return response()->json(responseFormatter(DEFAULT_UPDATE_200, TripStopResource::make($stop)));
    }

    public function timeline(string $tripRequestId): JsonResponse
    {
        $trip = $this->tripRequestService->findOneBy(criteria: ['id' => $tripRequestId]);
        if (!$trip) {
            return response()->json(responseFormatter(TRIP_REQUEST_404), 403);
        }

        $stops = $this->tripStopService->listByTrip($tripRequestId);
        $next = $this->tripStopService->nextPendingStop($tripRequestId);

        return response()->json(responseFormatter(DEFAULT_200, [
            'stops' => TripStopResource::collection($stops),
            'next_stop_id' => $next?->id,
            'all_completed' => $this->tripStopService->allStopsCompleted($tripRequestId),
        ]));
    }

    private function resolveDriverStop(string $stopId): TripStop|JsonResponse
    {
        $stop = TripStop::query()->with('tripRequest')->find($stopId);
        if (!$stop || $stop->tripRequest?->driver_id !== auth('api')->id()) {
            return response()->json(responseFormatter(TRIP_REQUEST_404), 403);
        }

        if (in_array($stop->status, ['completed', 'failed', 'expired'], true)) {
            return response()->json(responseFormatter(DEFAULT_400), 403);
        }

        return $stop;
    }
}
