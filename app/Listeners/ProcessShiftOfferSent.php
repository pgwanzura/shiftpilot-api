<?php

namespace App\Listeners;

use App\Events\ShiftOfferSent;
use App\Jobs\LockCandidate;
use App\Services\WebhookService;
use App\Notifications\ShiftOfferSentNotification;

class ProcessShiftOfferSent
{
    public function handle(ShiftOfferSent $event): void
    {
        LockCandidate::dispatch($event->shiftOffer->shift, $event->shiftOffer->employee);

        $event->shiftOffer->employee->user->notify(
            new ShiftOfferSentNotification($event->shiftOffer)
        );

        logger("Shift offer sent: {$event->shiftOffer->id}");

        WebhookService::dispatch('shift_offer.sent', [
            'shift_offer_id' => $event->shiftOffer->id,
            'shift_id' => $event->shiftOffer->shift_id,
            'employee_id' => $event->shiftOffer->employee_id,
            'expires_at' => $event->shiftOffer->expires_at->toISOString(),
            'sent_at' => now()->toISOString(),
        ]);
    }
}
