<?php

namespace App\Events;

use App\Models\Timesheet;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimesheetEmployerApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Timesheet $timesheet)
    {
    }
}
