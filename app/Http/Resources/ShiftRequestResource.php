<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'location_id' => $this->location_id,
            'title' => $this->title,
            'description' => $this->description,
            'role' => $this->role,
            'required_qualifications' => $this->required_qualifications,
            'experience_level' => $this->experience_level,
            'experience_level_label' => $this->getExperienceLevelLabel(),
            'background_check_required' => (bool) $this->background_check_required,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'shift_pattern' => $this->shift_pattern,
            'shift_pattern_label' => $this->getShiftPatternLabel(),
            'recurrence_rules' => $this->recurrence_rules,
            'max_hourly_rate' => (float) $this->max_hourly_rate,
            'currency' => $this->currency,
            'number_of_workers' => $this->number_of_workers,
            'target_agencies' => $this->target_agencies,
            'specific_agency_ids' => $this->specific_agency_ids,
            'response_deadline' => $this->response_deadline?->toISOString(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'created_by_id' => $this->created_by_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'location' => new LocationResource($this->whenLoaded('location')),
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'agency_assignment_responses' => AgencyAssignmentResponseResource::collection($this->whenLoaded('agencyResponses')),
            
            // Links
            'links' => [
                'self' => route('shift-requests.show', $this->id),
                'employer' => route('employers.show', $this->employer_id),
                'location' => route('locations.show', $this->location_id),
            ],
        ];
    }

    protected function getExperienceLevelLabel(): string
    {
        $levels = [
            'entry' => 'Entry Level',
            'intermediate' => 'Intermediate',
            'experienced' => 'Experienced',
            'senior' => 'Senior',
            'expert' => 'Expert',
        ];

        return $levels[$this->experience_level] ?? $this->experience_level;
    }

    protected function getShiftPatternLabel(): string
    {
        $patterns = [
            'one_time' => 'One Time',
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'custom' => 'Custom',
        ];

        return $patterns[$this->shift_pattern] ?? $this->shift_pattern;
    }

    protected function getStatusLabel(): string
    {
        $statuses = [
            'draft' => 'Draft',
            'published' => 'Published',
            'in_progress' => 'In Progress',
            'filled' => 'Filled',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}