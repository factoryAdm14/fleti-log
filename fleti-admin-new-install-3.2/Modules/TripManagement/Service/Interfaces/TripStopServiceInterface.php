<?php

namespace Modules\TripManagement\Service\Interfaces;

use App\Service\Interfaces\BaseServiceInterface;
use Modules\TripManagement\Entities\TripRequest;
use Modules\TripManagement\Entities\TripStop;

interface TripStopServiceInterface extends BaseServiceInterface
{
    public function createStopsForTrip(TripRequest $trip, array $stops): void;

    public function listByTrip(string $tripRequestId): mixed;

    public function markArrived(TripStop $stop): TripStop;

    public function markCompleted(TripStop $stop, array $payload = []): TripStop;

    public function allStopsCompleted(string $tripRequestId): bool;

    public function nextPendingStop(string $tripRequestId): ?TripStop;
}
