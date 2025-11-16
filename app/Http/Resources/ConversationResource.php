<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'conversation_type' => $this->conversation_type,
            'last_message' => new MessageResource($this->whenLoaded('lastMessage')),
            'last_message_at' => $this->last_message_at,
            'participants_count' => $this->getActiveParticipantsCount(),
            'participants' => UserResource::collection($this->whenLoaded('participants.user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
