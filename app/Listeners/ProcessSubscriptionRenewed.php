<?php

namespace App\Listeners;

use App\Events\SubscriptionRenewed;
use App\Jobs\ChargeSubscription;
use App\Jobs\RecordRevenue;
use App\Jobs\NotifySubscriber;
use App\Notifications\SubscriptionRenewedNotification;

class ProcessSubscriptionRenewed
{
    public function handle(SubscriptionRenewed $event): void
    {
        // Charge the subscription
        ChargeSubscription::dispatch($event->subscription);

        // Record platform revenue
        RecordRevenue::dispatch($event->subscription);

        // Notify the subscriber
        NotifySubscriber::dispatch($event->subscription);

        // Log the renewal
        logger("Subscription renewed: {$event->subscription->id} for {$event->subscription->entity_type} #{$event->subscription->entity_id}");
    }
}
