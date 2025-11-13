<?php

namespace App\Events\Agency;

use App\Models\Agency;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgencyStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Agency $agency,
        public string $previousStatus
    ) {}
}
