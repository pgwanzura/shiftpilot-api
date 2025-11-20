<?php
// app/Http/Resources/AssignmentResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'agency_employee_id' => $this->agency_employee_id,
            'shift_request_id' => $this->shift_request_id,
            'agency_response_id' => $this->agency_response_id,
            'location_id' => $this->location_id,
            'role' => $this->role,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'expected_hours_per_week' => $this->expected_hours_per_week,
            'agreed_rate' => (float) $this->agreed_rate,
            'pay_rate' => (float) $this->pay_rate,
            'markup_amount' => (float) $this->markup_amount,
            'markup_percent' => (float) $this->markup_percent,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'assignment_type' => $this->assignment_type->value,
            'assignment_type_label' => $this->assignment_type->label(),
            'shift_pattern' => $this->shift_pattern,
            'notes' => $this->notes,
            'created_by_id' => $this->created_by_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'contract' => new EmployerAgencyContractResource($this->whenLoaded('contract')),
            'agency_employee' => new AgencyEmployeeResource($this->whenLoaded('agencyEmployee')),
            'shift_request' => new ShiftRequestResource($this->whenLoaded('shiftRequest')),
            'agency_assingment_response' => new AgencyAssignmentResponseResource($this->whenLoaded('agencyResponse')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'shifts' => ShiftResource::collection($this->whenLoaded('shifts')),
            'timesheets' => TimesheetResource::collection($this->whenLoaded('timesheets')),

            // Computed attributes
            'is_active' => $this->isActive(),
            'is_completed' => $this->isCompleted(),
            'is_ongoing' => $this->is_ongoing,
            'duration_days' => $this->duration_days,
            'total_expected_hours' => $this->total_expected_hours,
            'can_be_updated' => $this->canBeUpdated(),
            'can_be_deleted' => $this->canBeDeleted(),

            // Financial summary
            'financial_summary' => $this->when(
                $request->user()?->can('viewFinancials', $this->resource),
                fn() => [
                    'hourly_margin' => (float) $this->markup_amount,
                    'margin_percentage' => (float) $this->markup_percent,
                    'weekly_margin' => $this->expected_hours_per_week ?
                        (float) $this->markup_amount * $this->expected_hours_per_week : null,
                ]
            ),

            // Analytics (for authorized users)
            'analytics' => $this->when(
                $request->user()?->can('viewAnalytics', $this->resource),
                fn() => $this->getAnalyticsData()
            ),
        ];
    }
}
