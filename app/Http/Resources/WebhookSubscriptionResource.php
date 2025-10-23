<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'url' => $this->url,
            'events' => $this->events,
            'secret' => $this->secret, // Consider hiding this in production
            'status' => $this->status,
            'last_delivery_at' => $this->last_delivery_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'owner' => $this->whenLoaded('owner'),
        ];
    }
}
