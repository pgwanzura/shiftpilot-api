<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyEmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'agency_branch_id' => $this->agency_branch_id,
            'employee_id' => $this->employee_id,
            'position' => $this->position,
            'pay_rate' => (float) $this->pay_rate,
            'employment_type' => $this->employment_type,
            'employment_type_label' => $this->getEmploymentTypeLabel(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'contract_start_date' => $this->contract_start_date?->toDateString(),
            'contract_end_date' => $this->contract_end_date?->toDateString(),
            'specializations' => $this->specializations,
            'preferred_locations' => $this->preferred_locations,
            'max_weekly_hours' => $this->max_weekly_hours,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'branch' => new AgencyBranchResource($this->whenLoaded('branch')),

            // Links
            'links' => [
                'self' => route('agency-employees.show', $this->id),
                'agency' => route('agencies.show', $this->agency_id),
                'employee' => route('employees.show', $this->employee_id),
            ],
        ];
    }

    protected function getEmploymentTypeLabel(): string
    {
        $types = [
            'temp' => 'Temporary',
            'contract' => 'Contract',
            'temp_to_perm' => 'Temp to Perm',
        ];

        return $types[$this->employment_type] ?? $this->employment_type;
    }

    protected function getStatusLabel(): string
    {
        $statuses = [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'terminated' => 'Terminated',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
