<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'registration_number' => $this->registration_number,
            'billing_email' => $this->billing_email,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'commission_rate' => $this->commission_rate,
            'subscription_status' => $this->subscription_status,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'agents' => AgentResource::collection($this->whenLoaded('agents')),
            'employees' => EmployeeResource::collection($this->whenLoaded('employees')),
            'employer_agency_links' => EmployerAgencyLinkResource::collection($this->whenLoaded('employerAgencyLinks')),
        ];
    }
}
