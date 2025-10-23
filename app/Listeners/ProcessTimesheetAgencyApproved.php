<?php

namespace App\Listeners;

use App\Events\TimesheetAgencyApproved;
use App\Jobs\MarkTimesheetAgencyApproved;
use App\Jobs\NotifyEmployerForSignoff;
use App\Services\WebhookService;
use App\Notifications\TimesheetAgencyApprovedNotification;

class ProcessTimesheetAgencyApproved
{
    public function handle(TimesheetAgencyApproved $event): void
    {
        MarkTimesheetAgencyApproved::dispatch($event->timesheet);

        NotifyEmployerForSignoff::dispatch($event->timesheet);

        logger("Timesheet agency approved: {$event->timesheet->id}");

        WebhookService::dispatch('timesheet.agency_approved', [
            'timesheet_id' => $event->timesheet->id,
            'approved_by' => $event->timesheet->agency_approved_by,
            'approved_at' => $event->timesheet->agency_approved_at->toISOString(),
        ]);
    }
}
