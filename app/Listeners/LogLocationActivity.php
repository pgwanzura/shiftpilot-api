<?php

// app/Listeners/LogLocationActivity.php

namespace App\Listeners;

use App\Events\LocationCreated;
use App\Events\LocationUpdated;
use App\Models\AuditLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogLocationActivity
{
    public function handleLocationCreated(LocationCreated $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'action' => 'location.created',
            'target_type' => 'location',
            'target_id' => $event->location->id,
            'payload' => [
                'name' => $event->location->name,
                'employer_id' => $event->location->employer_id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function handleLocationUpdated(LocationUpdated $event): void
    {
        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => auth()->id(),
            'action' => 'location.updated',
            'target_type' => 'location',
            'target_id' => $event->location->id,
            'payload' => [
                'name' => $event->location->name,
                'employer_id' => $event->location->employer_id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function subscribe($events): array
    {
        return [
            LocationCreated::class => 'handleLocationCreated',
            LocationUpdated::class => 'handleLocationUpdated',
        ];
    }
}
