<?php

// app/Listeners/NotifyAgencyOfNewLocation.php

namespace App\Listeners;

use App\Events\LocationCreated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotifyAgencyOfNewLocation implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private NotificationService $notificationService)
    {
    }

    public function handle(LocationCreated $event): void
    {
        $location = $event->location;
        $employer = $location->employer;

        // Notify all agencies connected to this employer
        foreach ($employer->agencies as $agency) {
            foreach ($agency->agents as $agent) {
                $this->notificationService->createNotification(
                    $agent->user,
                    'location.created',
                    [
                        'location_name' => $location->name,
                        'employer_name' => $employer->name,
                        'address' => $location->address,
                    ],
                    ['in_app']
                );
            }
        }
    }
}
