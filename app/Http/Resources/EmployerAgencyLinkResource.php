<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerAgencyLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'agency_id' => $this->agency_id,
            'status' => $this->status,
            'contract_document_url' => $this->contract_document_url,
            'contract_start' => $this->contract_start,
            'contract_end' => $this->contract_end,
            'terms' => $this->terms,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'agency' => new AgencyResource($this->whenLoaded('agency')),
        ];
    }
}
