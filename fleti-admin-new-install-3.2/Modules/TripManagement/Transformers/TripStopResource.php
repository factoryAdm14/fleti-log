<?php

namespace Modules\TripManagement\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_request_id' => $this->trip_request_id,
            'stop_order' => $this->stop_order,
            'type' => $this->type,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'arrived_at' => $this->arrived_at,
            'completed_at' => $this->completed_at,
            'proof_photo' => $this->proof_photo
                ? dynamicStorage('storage/app/public/trip/parcel/proof/stop/') . $this->proof_photo
                : null,
            'signature' => $this->signature,
            'qr_code' => $this->qr_code,
            'notes' => $this->notes,
        ];
    }
}
