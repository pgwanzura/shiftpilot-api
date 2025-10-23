<?php

namespace App\Listeners;

use App\Events\TimesheetEmployerApproved;
use App\Jobs\MarkTimesheetEmployerApproved;
use App\Jobs\GenerateInvoice;
use App\Services\WebhookService;
use App\Notifications\TimesheetEmployerApprovedNotification;

class ProcessTimesheetEmployerApproved
{
    public function handle(TimesheetEmployerApproved $event): void
    {
        MarkTimesheetEmployerApproved::dispatch($event->timesheet);

        GenerateInvoice::dispatch($event->timesheet);

        $agencyAdmins = $event->timesheet->shift->agency->agents;
        foreach ($agencyAdmins as $admin) {
            $admin->user->notify(new TimesheetEmployerApprovedNotification($event->timesheet));
        }

        logger("Timesheet employer approved: {$event->timesheet->id}");

        WebhookService::dispatch('timesheet.employer_approved', [
            'timesheet_id' => $event->timesheet->id,
            'approved_by_contact_id' => $event->timesheet->approved_by_contact_id,
            'approved_at' => $event->timesheet->approved_at->toISOString(),
        ]);
    }
}
