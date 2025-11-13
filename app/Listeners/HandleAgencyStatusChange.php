<?php

namespace App\Listeners;

use App\Events\Agency\AgencyStatusChanged;
use App\Models\Agency;
use App\Notifications\AgencyStatusUpdatedNotification;

class HandleAgencyStatusChange
{
    public function handle(AgencyStatusChanged $event): void
    {
        $event->agency->user->notify(
            new AgencyStatusUpdatedNotification($event->agency, $event->previousStatus)
        );

        if (!$event->agency->isActive()) {
            $this->suspendActiveOperations($event->agency);
        }
    }

    private function suspendActiveOperations(Agency $agency): void
    {
        $agency->agencyEmployees()->update(['status' => 'suspended']);

        $agency->assignments()
            ->where('status', 'active')
            ->update(['status' => 'suspended']);
    }
}
