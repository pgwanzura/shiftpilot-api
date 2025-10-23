<?php

namespace App\Jobs;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkTimesheetAgencyApproved implements ShouldQueue
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
        $this->timesheet->update([
            'status' => 'agency_approved',
            'agency_approved_at' => now(),
        ]);

        logger("Timesheet agency approved: {$this->timesheet->id}");

        // Trigger agency approved event
        event(new \App\Events\TimesheetAgencyApproved($this->timesheet));
    }
}
