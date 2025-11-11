<?php

namespace App\Events\Shift;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftCancelled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Shift $shift, public User $cancelledBy) {}
}
