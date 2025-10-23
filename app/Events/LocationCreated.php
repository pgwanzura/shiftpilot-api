<?php

namespace App\Events;

use App\Models\Location;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Location $location)
    {
    }
}
