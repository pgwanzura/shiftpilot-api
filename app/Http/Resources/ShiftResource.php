<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'location_id' => $this->location_id,
            'shift_date' => $this->shift_date->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'hourly_rate' => (float) $this->hourly_rate,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'notes' => $this->notes,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'duration_hours' => $this->duration_hours,
            'is_past' => $this->is_past,
            'is_future' => $this->is_future,
            'is_ongoing' => $this->is_ongoing,
            'total_earnings' => (float) $this->total_earnings,
            'can_be_updated' => $this->canBeUpdated(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_started' => $this->canBeStarted(),
            'can_be_completed' => $this->canBeCompleted(),
            'assignment' => new AssignmentResource($this->whenLoaded('assignment')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'timesheet' => new TimesheetResource($this->whenLoaded('timesheet')),
            'shift_approvals' => ShiftApprovalResource::collection($this->whenLoaded('shiftApprovals')),
            'shift_offers' => ShiftOfferResource::collection($this->whenLoaded('shiftOffers')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'validation' => $this->when(
                $request->user()?->can('validate', $this->resource),
                fn() => [
                    'has_overlaps' => $this->checkForOverlaps(),
                    'within_assignment_dates' => $this->isWithinAssignmentDates(),
                    'within_employee_availability' => $this->isWithinEmployeeAvailability(),
                ]
            ),
        ];
    }
}
