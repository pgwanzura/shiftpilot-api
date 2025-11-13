<?php

namespace App\Events;

use App\Models\Agency;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgencyUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Agency $agency, public array $changes) {}
}