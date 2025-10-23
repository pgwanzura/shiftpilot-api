<?php

namespace App\Listeners;

use App\Events\ShiftOffered;
use App\Jobs\LockCandidate;
use App\Jobs\NotifyEmployer;
use App\Services\WebhookService;
use App\Notifications\ShiftOfferedNotification;

class ProcessShiftOffered
{
    public function handle(ShiftOffered $event): void
    {
        LockCandidate::dispatch($event->shift, $event->employee);

        NotifyEmployer::dispatch($event->shift, $event->employee);

        logger("Shift offered to employee: {$event->employee->id} for shift: {$event->shift->id}");

        WebhookService::dispatch('shift.offered', [
        'shift_id' => $event->shift->id,
        'employee_id' => $event->employee->id,
        'offered_by_id' => $event->offeredBy->id,
        'offered_at' => now()->toISOString(),
    ]);
    }
}
