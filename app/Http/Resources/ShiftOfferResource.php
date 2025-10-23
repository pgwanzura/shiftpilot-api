<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftOfferResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'employee_id' => $this->employee_id,
            'offered_by_id' => $this->offered_by_id,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
            'responded_at' => $this->responded_at,
            'response_notes' => $this->response_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'shift' => $this->whenLoaded('shift'),
            'employee' => $this->whenLoaded('employee'),
            'offered_by' => $this->whenLoaded('offeredBy'),
        ];
    }
}
