<?php

namespace Modules\TripManagement\Repository\Eloquent;

use App\Repository\Eloquent\BaseRepository;
use Modules\TripManagement\Entities\TripStop;
use Modules\TripManagement\Repository\TripStopRepositoryInterface;

class TripStopRepository extends BaseRepository implements TripStopRepositoryInterface
{
    public function __construct(TripStop $model)
    {
        parent::__construct($model);
    }
}
