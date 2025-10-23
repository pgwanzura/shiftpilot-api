<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
            'status' => $this->status,
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'meta' => $this->meta,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'has_complete_profile' => $this->has_complete_profile,
            'formatted_address' => $this->formatted_address,
            'agency' => new AgencyResource($this->whenLoaded('agency')),
            'agent' => new AgentResource($this->whenLoaded('agent')),
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'contact' => new ContactResource($this->whenLoaded('contact')),
            'shift_offers_made' => ShiftOfferResource::collection($this->whenLoaded('shiftOffersMade')),
            'time_off_approvals' => TimeOffRequestResource::collection($this->whenLoaded('timeOffApprovals')),
            'agency_approved_timesheets' => TimesheetResource::collection($this->whenLoaded('agencyApprovedTimesheets')),
        ];
    }
}
