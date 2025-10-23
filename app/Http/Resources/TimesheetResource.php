<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'employee_id' => $this->employee_id,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'break_minutes' => $this->break_minutes,
            'hours_worked' => $this->hours_worked,
            'status' => $this->status,
            'agency_approved_by' => $this->agency_approved_by,
            'agency_approved_at' => $this->agency_approved_at,
            'approved_by_contact_id' => $this->approved_by_contact_id,
            'approved_at' => $this->approved_at,
            'notes' => $this->notes,
            'attachments' => $this->attachments,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'shift' => $this->whenLoaded('shift'),
            'employee' => $this->whenLoaded('employee'),
            'agency_approved_by_user' => $this->whenLoaded('agencyApprovedBy'),
            'approved_by_contact' => $this->whenLoaded('approvedByContact'),
        ];
    }
}
