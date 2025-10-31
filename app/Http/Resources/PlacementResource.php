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
            'employer_id' => $this->employer_id,
            'title' => $this->title,
            'description' => $this->description,
            'role_requirements' => $this->role_requirements,
            'required_qualifications' => $this->required_qualifications,
            'experience_level' => $this->experience_level,
            'background_check_required' => $this->background_check_required,
            'location_id' => $this->location_id,
            'location' => new LocationResource($this->whenLoaded('location')),
            'location_instructions' => $this->location_instructions,
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'shift_pattern' => $this->shift_pattern,
            'recurrence_rules' => $this->recurrence_rules,
            'budget_type' => $this->budget_type,
            'budget_amount' => (float) $this->budget_amount,
            'currency' => $this->currency,
            'overtime_rules' => $this->overtime_rules,
            'target_agencies' => $this->target_agencies,
            'specific_agency_ids' => $this->specific_agency_ids,
            'agencies' => AgencyResource::collection($this->whenLoaded('agencies')),
            'response_deadline' => $this->response_deadline?->toISOString(),
            'status' => $this->status,
            'selected_agency_id' => $this->selected_agency_id,
            'selected_employee_id' => $this->selected_employee_id,
            'selected_agency' => new AgencyResource($this->whenLoaded('selectedAgency')),
            'selected_employee' => new EmployeeResource($this->whenLoaded('selectedEmployee')),
            'agreed_rate' => $this->agreed_rate ? (float) $this->agreed_rate : null,
            'created_by_id' => $this->created_by_id,
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'agency_responses_count' => $this->whenCounted('agencyResponses'),
            'shifts_count' => $this->whenCounted('shifts'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'is_past_response_deadline' => $this->response_deadline?->isPast(),
            'days_until_start' => $this->start_date?->diffInDays(now()),
            'can_edit' => $this->isDraft(),
            'can_activate' => $this->canBeActivated(),
            'total_budget' => $this->calculateTotalBudget(),
        ];
    }
}
