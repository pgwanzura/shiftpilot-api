<?php

namespace App\Events\Timesheet;

use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimesheetRejected
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Timesheet $timesheet,
        public string $reason,
        public User $rejectedBy
    ) {
    }
}
