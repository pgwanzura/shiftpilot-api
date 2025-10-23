<?php

namespace App\Listeners;

use App\Events\TimesheetSubmitted;
use App\Services\WebhookService;
use App\Notifications\TimesheetSubmittedNotification;

class ProcessTimesheetSubmitted
{
    public function handle(TimesheetSubmitted $event): void
    {

        $agencyAdmins = $event->timesheet->shift->agency->agents;
        foreach ($agencyAdmins as $admin) {
            $admin->user->notify(new TimesheetSubmittedNotification($event->timesheet));
        }

        logger("Timesheet submitted: {$event->timesheet->id}");

        WebhookService::dispatch('timesheet.submitted', [
            'timesheet_id' => $event->timesheet->id,
            'shift_id' => $event->timesheet->shift_id,
            'employee_id' => $event->timesheet->employee_id,
            'hours_worked' => $event->timesheet->hours_worked,
            'submitted_at' => now()->toISOString(),
    ]);
    }
}
