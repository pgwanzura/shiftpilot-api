<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyAssignmentResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'agency_id' => $this->agency_id,
            'proposal_text' => $this->proposal_text,
            'proposed_rate' => (float) $this->proposed_rate,
            'estimated_hours' => $this->estimated_hours,
            'total_proposed_amount' => $this->getTotalProposedAmount(),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'rejection_reason' => $this->rejection_reason,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'responded_at' => $this->responded_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relationships
            'assignment' => new AssignmentResource($this->whenLoaded('assignment')),
            'agency' => new AgencyResource($this->whenLoaded('agency')),

            // Links
            'links' => [
                'self' => route('agency-assignment-responses.show', $this->id),
                'assignment' => $this->assignment_id ? route('assignments.show', $this->assignment_id) : null,
                'agency' => $this->agency_id ? route('agencies.show', $this->agency_id) : null,
            ],
        ];
    }

    protected function getStatusLabel(): string
    {
        $statuses = [
            'submitted' => 'Submitted',
            'reviewed' => 'Reviewed',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}
