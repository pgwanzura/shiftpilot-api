<?php

namespace App\Events\TimeOff;

use App\Models\TimeOffRequest;
use Illuminate\Foundation\Events\Dispatchable;

class TimeOffRejected
{
    use Dispatchable;

    public function __construct(
        public TimeOffRequest $timeOffRequest
    ) {}
}
