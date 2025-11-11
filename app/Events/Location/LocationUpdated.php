<?php

namespace App\Events\Location;

use App\Models\Location;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Location $location) {}
}
