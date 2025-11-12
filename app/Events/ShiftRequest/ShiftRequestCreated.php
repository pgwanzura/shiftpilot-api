<?php

namespace App\Events\ShiftRequest;

use App\Models\ShiftRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftRequestCreated
{
    use Dispatchable;
    use SerializesModels;

    public $shiftRequest;

    public function __construct(ShiftRequest $shiftRequest)
    {
        $this->shiftRequest = $shiftRequest;
    }
}
