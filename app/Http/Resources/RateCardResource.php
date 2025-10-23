<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RateCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'agency_id' => $this->agency_id,
            'role_key' => $this->role_key,
            'location_id' => $this->location_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'rate' => $this->rate,
            'currency' => $this->currency,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'location' => new LocationResource($this->whenLoaded('location')),
        ];
    }
}
