<?php

namespace Modules\TripManagement\Service;

use App\Service\BaseService;
use Modules\TripManagement\Entities\TripRequest;
use Modules\TripManagement\Entities\TripStop;
use Modules\TripManagement\Lib\MultiStopHelper;
use Modules\TripManagement\Repository\TripStopRepositoryInterface;
use Modules\TripManagement\Service\Interfaces\TripStopServiceInterface;

class TripStopService extends BaseService implements TripStopServiceInterface
{
    public function __construct(TripStopRepositoryInterface $tripStopRepository)
    {
        parent::__construct($tripStopRepository);
    }

    public function createStopsForTrip(TripRequest $trip, array $stops): void
    {
        MultiStopHelper::validateStops($stops);
        $stops = MultiStopHelper::optimizeStopOrder($stops);

        foreach ($stops as $stop) {
            TripStop::query()->create([
                'trip_request_id' => $trip->id,
                'stop_order' => (int) $stop['stop_order'],
                'type' => $stop['type'],
                'address' => $stop['address'],
                'latitude' => $stop['latitude'],
                'longitude' => $stop['longitude'],
                'notes' => $stop['notes'] ?? null,
                'status' => 'pending',
            ]);
        }

        $trip->update(['is_multi_stop' => true]);
    }

    public function listByTrip(string $tripRequestId): mixed
    {
        return TripStop::query()
            ->where('trip_request_id', $tripRequestId)
            ->orderBy('stop_order')
            ->get();
    }

    public function markArrived(TripStop $stop): TripStop
    {
        $stop->update([
            'status' => 'arrived',
            'arrived_at' => now(),
        ]);

        return $stop->fresh();
    }

    public function markCompleted(TripStop $stop, array $payload = []): TripStop
    {
        $stop->update([
            'status' => 'completed',
            'completed_at' => now(),
            'proof_photo' => $payload['proof_photo'] ?? $stop->proof_photo,
            'signature' => $payload['signature'] ?? $stop->signature,
            'qr_code' => $payload['qr_code'] ?? $stop->qr_code,
            'notes' => $payload['notes'] ?? $stop->notes,
        ]);

        return $stop->fresh();
    }

    public function allStopsCompleted(string $tripRequestId): bool
    {
        return !TripStop::query()
            ->where('trip_request_id', $tripRequestId)
            ->whereNotIn('status', ['completed'])
            ->exists();
    }

    public function nextPendingStop(string $tripRequestId): ?TripStop
    {
        return TripStop::query()
            ->where('trip_request_id', $tripRequestId)
            ->whereIn('status', ['pending', 'arrived'])
            ->orderBy('stop_order')
            ->first();
    }
}
