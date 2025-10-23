<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    use HasFactory;

    protected $table = 'webhook_subscriptions';

    protected $fillable = [
        'owner_type', 'owner_id', 'url', 'events', 'secret',
        'status', 'last_delivery_at'
    ];

    protected $casts = [
        'events' => 'array',
        'last_delivery_at' => 'datetime'
    ];

    public function owner()
    {
        return $this->morphTo();
    }
}
