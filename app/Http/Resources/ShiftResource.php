<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'agency_id' => $this->agency_id,
            'placement_id' => $this->placement_id,
            'employee_id' => $this->employee_id,
            'agent_id' => $this->agent_id,
            'location_id' => $this->location_id,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'hourly_rate' => $this->hourly_rate,
            'status' => $this->status,
            'created_by_type' => $this->created_by_type,
            'created_by_id' => $this->created_by_id,
            'meta' => $this->meta,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employer' => $this->whenLoaded('employer'),
            'agency' => $this->whenLoaded('agency'),
            'placement' => $this->whenLoaded('placement'),
            'employee' => $this->whenLoaded('employee'),
            'agent' => $this->whenLoaded('agent'),
            'location' => $this->whenLoaded('location'),
            'timesheet' => $this->whenLoaded('timesheet'),
            'shift_approvals' => $this->whenLoaded('shiftApprovals'),
        ];
    }
}
