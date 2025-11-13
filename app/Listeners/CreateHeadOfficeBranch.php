<?php

namespace App\Listeners;

use App\Events\AgencyCreated;
use App\Models\AgencyBranch;

class CreateHeadOfficeBranch
{
    public function handle(AgencyCreated $event): void
    {
        AgencyBranch::create([
            'agency_id' => $event->agency->id,
            'name' => 'Head Office',
            'is_head_office' => true,
            'status' => 'active',
        ]);
    }
}
