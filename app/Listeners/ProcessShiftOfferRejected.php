<?php

namespace App\Listeners;

use App\Events\ShiftOfferRejected;
use App\Jobs\UnlockCandidate;
use App\Services\WebhookService;
use App\Notifications\ShiftOfferRejectedNotification;

class ProcessShiftOfferRejected
{
    public function handle(ShiftOfferRejected $event): void
    {
        UnlockCandidate::dispatch($event->shiftOffer->employee);

        $agencyAdmins = $event->shiftOffer->shift->agency->agents;
        foreach ($agencyAdmins as $admin) {
            $admin->user->notify(new ShiftOfferRejectedNotification($event->shiftOffer));
        }

        logger("Shift offer rejected: {$event->shiftOffer->id}");

        WebhookService::dispatch('shift_offer.rejected', [
            'shift_offer_id' => $event->shiftOffer->id,
            'rejected_at' => $event->shiftOffer->responded_at->toISOString(),
            'reason' => $event->shiftOffer->response_notes,
        ]);
    }
}
