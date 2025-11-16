<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'content' => $this->content,
            'message_type' => $this->message_type,
            'attachments' => $this->attachments,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
            'recipients' => MessageRecipientResource::collection($this->whenLoaded('recipients')),
        ];
    }
}
