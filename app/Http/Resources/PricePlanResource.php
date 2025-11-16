<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PricePlanResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'plan_key' => $this->plan_key,
            'name' => $this->name,
            'description' => $this->description,
            'base_amount' => $this->base_amount,
            'billing_interval' => $this->billing_interval,
            'monthly_amount' => $this->getMonthlyAmount(),
            'yearly_amount' => $this->getYearlyAmount(),
            'features' => $this->features,
            'limits' => $this->limits,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function getMonthlyAmount(float $discountPercentage = 20.0): float
    {
        if ($this->billing_interval === 'monthly') {
            return $this->base_amount;
        }

        $monthlyAmount = $this->base_amount / 12;
        $discountMultiplier = 1 + ($discountPercentage / 100);

        return round($monthlyAmount * $discountMultiplier, 2);
    }

    protected function getYearlyAmount(float $discountPercentage = 20.0): float
    {
        if ($this->billing_interval === 'yearly') {
            return $this->base_amount;
        }

        $yearlyAmount = $this->base_amount * 12;
        $discountMultiplier = 1 - ($discountPercentage / 100);

        return round($yearlyAmount * $discountMultiplier, 2);
    }
}
