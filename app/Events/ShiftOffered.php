<?php

namespace App\Events;

use App\Models\Shift;
use App\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftOffered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Shift $shift,
        public Employee $employee,
        public $offeredBy
    ) {
    }
}
