<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'agency_id' => $this->agency_id,
            'employer_id' => $this->employer_id,
            'position' => $this->position,
            'pay_rate' => $this->pay_rate,
            'availability' => $this->availability,
            'qualifications' => $this->qualifications,
            'employment_type' => $this->employment_type,
            'status' => $this->status,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'availabilities' => EmployeeAvailabilityResource::collection($this->whenLoaded('employeeAvailabilities')),
            'time_off_requests' => TimeOffRequestResource::collection($this->whenLoaded('timeOffRequests')),
            'placements' => PlacementResource::collection($this->whenLoaded('placements')),
            'shifts' => ShiftResource::collection($this->whenLoaded('shifts')),
        ];
    }
}
