<?php

namespace App\Listeners;

use App\Events\AvailabilityUpdated;
use App\Jobs\ValidateAvailability;
use App\Jobs\UpdateEmployeeCalendar;
use App\Services\WebhookService;
use App\Notifications\AvailabilityUpdatedNotification;

class ProcessAvailabilityUpdated
{
    public function handle(AvailabilityUpdated $event): void
    {

        ValidateAvailability::dispatch($event->employee);

        UpdateEmployeeCalendar::dispatch($event->employee);

        $agencyAdmins = $event->employee->agency->agents;
        foreach ($agencyAdmins as $admin) {
            $admin->user->notify(new AvailabilityUpdatedNotification($event->employee));
        }

        logger("Availability updated for employee: {$event->employee->id}");

        WebhookService::dispatch('availability.updated', [
            'employee_id' => $event->employee->id,
            'updated_at' => now()->toISOString(),
        ]);
    }
}
