<?php

namespace App\Listeners;

use App\Events\ShiftCompleted;
use App\Jobs\CreateTimesheet;
use App\Jobs\NotifyAgencyForApproval;
use App\Services\WebhookService;
use App\Notifications\TimesheetSubmittedNotification;

class ProcessShiftCompleted
{
    public function handle(ShiftCompleted $event): void
    {
        CreateTimesheet::dispatch($event->shift);

        NotifyAgencyForApproval::dispatch($event->shift);

        logger("Shift completed: {$event->shift->id}");

        WebhookService::dispatch('shift.completed', [
            'shift_id' => $event->shift->id,
            'completed_at' => now()->toISOString(),
        ]);
    }
}
