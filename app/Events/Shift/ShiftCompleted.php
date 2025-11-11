<?php

namespace App\Events\Shift;

use App\Models\Shift;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Shift $shift) {}
}
