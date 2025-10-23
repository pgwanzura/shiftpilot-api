<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'contact_id' => $this->contact_id,
            'status' => $this->status,
            'signed_at' => $this->signed_at,
            'signature_blob_url' => $this->signature_blob_url,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'contact' => new ContactResource($this->whenLoaded('contact')),
        ];
    }
}
