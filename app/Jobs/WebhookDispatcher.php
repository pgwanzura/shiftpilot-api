<?php

namespace App\Jobs;

use App\Models\WebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WebhookDispatcher implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public WebhookSubscription $webhookSubscription,
        public string $event,
        public array $payload
    ) {
    }

    public function handle(): void
    {
        if ($this->webhookSubscription->status !== 'active') {
            return;
        }

        if (!in_array($this->event, $this->webhookSubscription->events)) {
            return;
        }

        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->post($this->webhookSubscription->url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-ShiftPilot-Signature' => $this->generateSignature(),
                    'X-ShiftPilot-Event' => $this->event,
                ],
                'json' => $this->payload,
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() === 200) {
                $this->webhookSubscription->update([
                    'last_delivery_at' => now(),
                ]);

                logger("Webhook delivered successfully: {$this->webhookSubscription->id}");
            }
        } catch (\Exception $e) {
            logger("Webhook delivery failed: {$this->webhookSubscription->id} - {$e->getMessage()}");
            throw $e;
        }
    }

    private function generateSignature(): string
    {
        $payload = json_encode($this->payload);
        return hash_hmac('sha256', $payload, $this->webhookSubscription->secret);
    }
}
