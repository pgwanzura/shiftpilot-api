<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'plan_key' => $this->plan_key,
            'plan_name' => $this->plan_name,
            'amount' => $this->amount,
            'interval' => $this->interval,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'current_period_start' => $this->current_period_start,
            'current_period_end' => $this->current_period_end,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'subscriber' => $this->whenLoaded('subscriber'),
        ];
    }
}
