<?php

namespace App\Listeners;

use App\Events\ShiftRequested;
use App\Jobs\ValidateShift;
use App\Jobs\NotifyAgencies;
use App\Notifications\ShiftRequestedNotification;

class ProcessShiftRequested
{
    public function handle(ShiftRequested $event): void
    {
        ValidateShift::dispatch($event->shift);

        NotifyAgencies::dispatch($event->shift);

        logger("Shift requested: {$event->shift->id}");

        \App\Services\WebhookService::dispatch('shift.requested', [
        'shift_id' => $event->shift->id,
        'employer_id' => $event->shift->employer_id,
        'location_id' => $event->shift->location_id,
        'start_time' => $event->shift->start_time->toISOString(),
        'end_time' => $event->shift->end_time->toISOString(),
        'requested_at' => now()->toISOString(),
    ]);
    }
}
