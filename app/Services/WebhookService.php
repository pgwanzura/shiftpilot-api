<?php

namespace App\Services;

use App\Jobs\WebhookDispatcher;
use App\Models\WebhookSubscription;

class WebhookService
{
    public static function dispatch(string $event, array $payload): void
    {
        $webhookSubscriptions = WebhookSubscription::where('status', 'active')
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhookSubscriptions as $webhook) {
            WebhookDispatcher::dispatch($webhook, $event, $payload);
        }
    }

    public static function dispatchToOwner(string $event, array $payload, $owner): void
    {
        $webhookSubscriptions = WebhookSubscription::where('status', 'active')
            ->where('owner_type', get_class($owner))
            ->where('owner_id', $owner->id)
            ->whereJsonContains('events', $event)
            ->get();

        foreach ($webhookSubscriptions as $webhook) {
            WebhookDispatcher::dispatch($webhook, $event, $payload);
        }
    }
}
