<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerAgencyContractResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'agency_id' => $this->agency_id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'contract_document_url' => $this->contract_document_url,
            'contract_start' => $this->contract_start?->toDateString(),
            'contract_end' => $this->contract_end?->toDateString(),
            'terms' => $this->terms,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'agency' => new AgencyResource($this->whenLoaded('agency')),

            // Links
            'links' => [
                'self' => route('employer-agency-contracts.show', $this->id),
                'employer' => route('employers.show', $this->employer_id),
                'agency' => route('agencies.show', $this->agency_id),
            ],
        ];
    }

    protected function getStatusLabel(): string
    {
        $statuses = [
            'pending' => 'Pending',
            'active' => 'Active',
            'suspended' => 'Suspended',
            'terminated' => 'Terminated',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
