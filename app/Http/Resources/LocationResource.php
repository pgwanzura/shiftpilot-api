<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'shifts' => ShiftResource::collection($this->whenLoaded('shifts')),
            'rate_cards' => RateCardResource::collection($this->whenLoaded('rateCards')),
        ];
    }
}
