<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SystemNotification extends Model
{
    protected $table = 'system_notifications'; // Explicitly define table name

    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'channel',
        'template_key',
        'payload',
        'is_read',
        'sent_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'payload' => 'array',
    ];

    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }
}
