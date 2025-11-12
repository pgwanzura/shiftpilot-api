<?php

namespace App\Events\AgencyResponse;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\AgencyResponse;

class AgencyResponseAccepted
{
    use Dispatchable, SerializesModels;

    public $agencyResponse;

    public function __construct(AgencyResponse $agencyResponse)
    {
        $this->agencyResponse = $agencyResponse;
    }
}
