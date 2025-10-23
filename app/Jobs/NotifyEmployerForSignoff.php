<?php

namespace App\Jobs;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyEmployerForSignoff implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Timesheet $timesheet)
    {
    }

    public function handle(): void
    {
        $employer = $this->timesheet->shift->employer;
        $contacts = $employer->contacts->where('can_sign_timesheets', true);

        foreach ($contacts as $contact) {
            if ($contact->user) {
                $contact->user->notify(new \App\Notifications\TimesheetAgencyApprovedNotification($this->timesheet));
            }
        }

        logger("Employer notified for timesheet signoff: {$this->timesheet->id}");
    }
}
