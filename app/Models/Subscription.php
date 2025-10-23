<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Subscription extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'plan_key',
        'plan_name',
        'amount',
        'interval',
        'status',
        'started_at',
        'current_period_start',
        'current_period_end',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'started_at' => 'datetime',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'meta' => 'array',
    ];

    public function subscriber(): MorphTo
    {
        return $this->morphTo('entity');
    }
}
