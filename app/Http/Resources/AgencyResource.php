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
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'registration_number' => $this->registration_number,
            'billing_email' => $this->billing_email,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'default_markup_percent' => $this->default_markup_percent,
            'subscription_status' => $this->subscription_status,
            'active_employees_count' => $this->whenCounted('agencyEmployees', function () {
                return $this->agency_employees_count;
            }),
            'active_assignments_count' => $this->whenCounted('assignments', function () {
                return $this->assignments_count;
            }),
            'user' => new UserResource($this->whenLoaded('user')),
            'head_office' => new AgencyBranchResource($this->whenLoaded('headOffice')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
