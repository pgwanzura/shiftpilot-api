<?php

// app/Events/ShiftCancelled.php

namespace App\Events;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftCancelled
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Shift $shift, public User $cancelledBy)
    {
    }
}
