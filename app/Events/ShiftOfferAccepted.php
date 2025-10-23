<?php

namespace App\Events;

use App\Models\ShiftOffer;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShiftOfferAccepted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ShiftOffer $shiftOffer)
    {
    }
}
