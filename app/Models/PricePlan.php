<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_key',
        'name',
        'description',
        'base_amount',
        'billing_interval',
        'features',
        'limits',
        'is_active',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'features' => 'array',
        'limits' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_key', 'plan_key');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMonthly($query)
    {
        return $query->where('billing_interval', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('billing_interval', 'yearly');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('base_amount');
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    public function getLimit(string $limit): ?int
    {
        return $this->limits[$limit] ?? null;
    }

    public function getYearlyAmount(): float
    {
        if ($this->billing_interval === 'yearly') {
            return $this->base_amount;
        }

        return $this->base_amount * 12;
    }

    public function getMonthlyAmount(): float
    {
        if ($this->billing_interval === 'monthly') {
            return $this->base_amount;
        }

        return $this->base_amount / 12;
    }

    public function isAvailable(): bool
    {
        return $this->is_active;
    }
}
