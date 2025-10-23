<?php

namespace App\Jobs;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAgencyForApproval implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Shift $shift)
    {
    }

    public function handle(): void
    {
        $agency = $this->shift->agency;
        $agencyAdmins = $agency->agents;

        foreach ($agencyAdmins as $admin) {
            $admin->user->notify(new \App\Notifications\TimesheetSubmittedNotification(
                $this->shift->timesheet
            ));
        }

        logger("Agency notified for timesheet approval: {$this->shift->id}");
    }
}
