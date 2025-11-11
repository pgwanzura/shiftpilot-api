<?php

namespace App\Events\Shift;

use App\Models\Shift;
use App\Models\Employee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Shift $shift,
        public Employee $employee
    ) {}
}
