<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageRecipientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user_id' => $this->user_id,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
