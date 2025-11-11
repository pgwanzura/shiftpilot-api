<?php

namespace App\Events\Timesheet;

use App\Models\Timesheet;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimesheetApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Timesheet $timesheet,
        public string $approvedBy // 'agency' or 'employer'
    ) {
    }
}
