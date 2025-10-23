<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employer_id' => $this->employer_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'can_sign_timesheets' => $this->can_sign_timesheets,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'employer' => new EmployerResource($this->whenLoaded('employer')),
            'user' => new UserResource($this->whenLoaded('user')),
            'shift_approvals' => ShiftApprovalResource::collection($this->whenLoaded('shiftApprovals')),
        ];
    }
}
