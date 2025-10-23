<?php

namespace App\Events;

use App\Models\TimeOffRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimeOffApproved
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TimeOffRequest $timeOffRequest)
    {
    }
}
