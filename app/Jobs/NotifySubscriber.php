<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Notifications\SubscriptionRenewedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifySubscriber implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Subscription $subscription)
    {
    }

    public function handle(): void
    {
        $subscriber = $this->subscription->entity;

        if ($subscriber && method_exists($subscriber, 'notify')) {
            $subscriber->notify(new SubscriptionRenewedNotification($this->subscription));
        }

        logger("Subscriber notified about renewal: {$this->subscription->id}");
    }
}
