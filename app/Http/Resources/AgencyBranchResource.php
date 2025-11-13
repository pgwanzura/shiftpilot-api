<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgencyBranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'branch_code' => $this->branch_code,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'county' => $this->county,
                'postcode' => $this->postcode,
                'country' => $this->country,
                'full_address' => $this->full_address,
            ],
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'contact' => [
                'name' => $this->contact_name,
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
            ],
            'is_head_office' => $this->is_head_office,
            'status' => $this->status,
            'opening_hours' => $this->opening_hours,
            'services_offered' => $this->services_offered,
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'agents_count' => $this->whenCounted('agents'),
            'employees_count' => $this->whenCounted('agencyEmployees'),
            'assignments_count' => $this->whenCounted('assignments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
