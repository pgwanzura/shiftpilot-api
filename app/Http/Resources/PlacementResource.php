<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlacementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employer_id' => $this->employer_id,
            'agency_id' => $this->agency_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'employee_rate' => $this->employee_rate,
            'client_rate' => $this->client_rate,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'shifts' => ShiftResource::collection($this->whenLoaded('shifts')),
        ];
    }
}
