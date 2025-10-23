<?php

namespace App\Jobs;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MarkTimesheetEmployerApproved implements ShouldQueue
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
            'status' => 'employer_approved',
            'approved_at' => now(),
        ]);

        // Update shift status to billed
        $this->timesheet->shift->update([
            'status' => 'billed',
        ]);

        logger("Timesheet employer approved: {$this->timesheet->id}");

        // Trigger employer approved event
        event(new \App\Events\TimesheetEmployerApproved($this->timesheet));
    }
}
