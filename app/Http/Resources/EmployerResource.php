<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'billing_email' => $this->billing_email,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'subscription_status' => $this->subscription_status,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),
            'shifts' => ShiftResource::collection($this->whenLoaded('shifts')),
            'employer_agency_links' => EmployerAgencyLinkResource::collection($this->whenLoaded('employerAgencyLinks')),
        ];
    }
}
