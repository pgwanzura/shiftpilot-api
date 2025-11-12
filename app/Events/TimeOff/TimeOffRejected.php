<?php

namespace App\Events\TimeOff;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\TimeOffRequest;

class TimeOffRejected
{
    use Dispatchable, SerializesModels;

    public $timeOffRequest;

    public function __construct(TimeOffRequest $timeOffRequest)
    {
        $this->timeOffRequest = $timeOffRequest;
    }
}
