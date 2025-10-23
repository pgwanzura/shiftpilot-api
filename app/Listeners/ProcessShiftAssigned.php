<?php

namespace App\Listeners;

use App\Events\ShiftAssigned;
use App\Jobs\CreatePlacementIfMissing;
use App\Jobs\CreateAssignment;
use App\Services\WebhookService;
use App\Notifications\ShiftAssignedNotification;

class ProcessShiftAssigned
{
    public function handle(ShiftAssigned $event): void
    {

        CreatePlacementIfMissing::dispatch($event->shift, $event->employee);

        CreateAssignment::dispatch($event->shift, $event->employee);

        $event->employee->user->notify(new ShiftAssignedNotification($event->shift));

        logger("Shift assigned: {$event->shift->id} to employee: {$event->employee->id}");

        WebhookService::dispatch('shift.assigned', [
            'shift_id' => $event->shift->id,
            'employee_id' => $event->employee->id,
            'employer_id' => $event->shift->employer_id,
            'agency_id' => $event->shift->agency_id,
            'assigned_at' => now()->toISOString(),
        ]);
    }
}
