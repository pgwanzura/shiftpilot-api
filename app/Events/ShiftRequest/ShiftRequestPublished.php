<?php

namespace App\Events\ShiftRequest;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ShiftRequest;

class ShiftRequestPublished
{
    use Dispatchable, SerializesModels;

    public $shiftRequest;

    public function __construct(ShiftRequest $shiftRequest)
    {
        $this->shiftRequest = $shiftRequest;
    }
}
