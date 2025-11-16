<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'agency_id' => $this->agency_id,
            'plan_id' => $this->plan_id,
            'amount' => $this->amount,
            'interval' => $this->interval,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'current_period_start' => $this->current_period_start,
            'current_period_end' => $this->current_period_end,
            'remaining_days' => $this->getRemainingDays(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'agency' => $this->whenLoaded('agency'),
            'plan' => $this->whenLoaded('plan'),
        ];
    }

    protected function getRemainingDays(): ?int
    {
        if ($this->current_period_end === null) {
            return null;
        }

        $now = now();
        if ($now->greaterThan($this->current_period_end)) {
            return 0;
        }

        return $now->diffInDays($this->current_period_end);
    }

    protected function isActive(): bool
    {
        return $this->status === 'active';
    }

    protected function isExpired(): bool
    {
        if ($this->current_period_end === null) {
            return false;
        }

        return now()->greaterThan($this->current_period_end);
    }
}

