<?php

namespace App\Listeners;

use App\Events\AgencyCreated;
use App\Events\AgencyUpdated;
use App\Events\Agency\AgencyStatusChanged;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\User;

class LogAgencyActivity
{
    public function handleAgencyCreated(AgencyCreated $event): void
    {
        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => $event->agency->user_id,
            'action' => 'agency_created',
            'target_type' => Agency::class,
            'target_id' => $event->agency->id,
            'payload' => $event->agency->toArray(),
        ]);
    }

    public function handleAgencyUpdated(AgencyUpdated $event): void
    {
        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => $event->agency->user_id,
            'action' => 'agency_updated',
            'target_type' => Agency::class,
            'target_id' => $event->agency->id,
            'payload' => $event->changes,
        ]);
    }

    public function handleAgencyStatusChanged(AgencyStatusChanged $event): void
    {
        AuditLog::create([
            'actor_type' => User::class,
            'actor_id' => $event->agency->user_id,
            'action' => 'agency_status_changed',
            'target_type' => Agency::class,
            'target_id' => $event->agency->id,
            'payload' => [
                'previous_status' => $event->previousStatus,
                'new_status' => $event->agency->subscription_status->value,
            ],
        ]);
    }
}
