<?php

// app/Events/TimesheetDeleted.php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimesheetDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public int $timesheetId,
        public array $timesheetData
    ) {
    }
}
