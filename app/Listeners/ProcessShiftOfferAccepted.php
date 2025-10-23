<?php

namespace App\Listeners;

use App\Events\ShiftOfferAccepted;
use App\Jobs\CreatePlacementIfMissing;
use App\Jobs\CreateAssignment;
use App\Services\WebhookService;
use App\Notifications\ShiftOfferAcceptedNotification;

class ProcessShiftOfferAccepted
{
    public function handle(ShiftOfferAccepted $event): void
    {
        CreatePlacementIfMissing::dispatch($event->shiftOffer->shift, $event->shiftOffer->employee);

        CreateAssignment::dispatch($event->shiftOffer->shift, $event->shiftOffer->employee);

        $agencyAdmins = $event->shiftOffer->shift->agency->agents;
        foreach ($agencyAdmins as $admin) {
            $admin->user->notify(new ShiftOfferAcceptedNotification($event->shiftOffer));
        }

        logger("Shift offer accepted: {$event->shiftOffer->id}");

        WebhookService::dispatch('shift_offer.accepted', [
            'shift_offer_id' => $event->shiftOffer->id,
            'accepted_at' => $event->shiftOffer->responded_at->toISOString(),
        ]);
    }
}
