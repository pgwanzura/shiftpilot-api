<?php

namespace App\Listeners;

use App\Events\TimeOffRequested;
use App\Jobs\CheckShiftConflicts;
use App\Jobs\NotifyAgency;
use App\Services\WebhookService;
use App\Notifications\TimeOffRequestedNotification;

class ProcessTimeOffRequested
{
    public function handle(TimeOffRequested $event): void
    {
        CheckShiftConflicts::dispatch($event->timeOffRequest);

        $agencyAdmins = $event->timeOffRequest->employee->agency->agents;
        foreach ($agencyAdmins as $admin) {
            $admin->user->notify(new TimeOffRequestedNotification($event->timeOffRequest));
        }

        logger("Time off requested: {$event->timeOffRequest->id}");

        WebhookService::dispatch('time_off.requested', [
            'time_off_request_id' => $event->timeOffRequest->id,
            'employee_id' => $event->timeOffRequest->employee_id,
            'type' => $event->timeOffRequest->type,
            'start_date' => $event->timeOffRequest->start_date->toISOString(),
            'end_date' => $event->timeOffRequest->end_date->toISOString(),
            'requested_at' => now()->toISOString(),
        ]);
    }
}
