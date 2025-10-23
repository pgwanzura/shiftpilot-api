<?php

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ChargeSubscription implements ShouldQueue
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
        try {
            // Process payment through payment provider (Stripe, etc.)
            $paymentResult = $this->processPayment();

            if ($paymentResult['success']) {
                // Update subscription with new period
                $this->subscription->update([
                    'current_period_start' => now(),
                    'current_period_end' => $this->getNextBillingDate(),
                    'status' => 'active',
                ]);

                logger("Subscription charged successfully: {$this->subscription->id}");
            } else {
                // Handle payment failure
                $this->subscription->update([
                    'status' => 'past_due',
                ]);

                logger("Subscription charge failed: {$this->subscription->id} - {$paymentResult['error']}");
            }
        } catch (\Exception $e) {
            logger("Subscription charge error: {$e->getMessage()}");
            throw $e;
        }
    }

    private function processPayment(): array
    {
        // Integrate with Stripe, PayPal, or other payment providers
        // This is a simplified implementation

        // Simulate API call to payment provider
        sleep(1);

        // In real implementation, this would call:
        // Stripe\Charge::create(), PayPal API, etc.

        return [
            'success' => true,
            'transaction_id' => 'ch_' . uniqid(),
            'error' => null,
        ];
    }

    private function getNextBillingDate(): \Carbon\Carbon
    {
        return match ($this->subscription->interval) {
            'yearly' => now()->addYear(),
            'monthly' => now()->addMonth(),
            default => now()->addMonth(),
        };
    }
}
