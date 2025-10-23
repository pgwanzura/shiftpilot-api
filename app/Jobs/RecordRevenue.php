<?php

namespace App\Jobs;

use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordRevenue implements ShouldQueue
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
        // Create an invoice for the subscription revenue
        $invoice = Invoice::create([
            'type' => 'subscription_renewal',
            'from_type' => $this->subscription->entity_type,
            'from_id' => $this->subscription->entity_id,
            'to_type' => 'shiftpilot',
            'to_id' => 1, // Platform ID
            'reference' => 'SUB-' . now()->format('Ymd-His'),
            'line_items' => $this->generateLineItems(),
            'subtotal' => $this->subscription->amount,
            'total_amount' => $this->subscription->amount,
            'due_date' => now(),
            'status' => 'paid',
            'paid_at' => now(),
            'metadata' => [
                'subscription_id' => $this->subscription->id,
                'plan_key' => $this->subscription->plan_key,
                'interval' => $this->subscription->interval,
            ],
        ]);

        logger("Revenue recorded for subscription: {$this->subscription->id}, invoice: {$invoice->id}");
    }

    private function generateLineItems(): array
    {
        return [
            [
                'description' => "{$this->subscription->plan_name} Subscription ({$this->subscription->interval})",
                'quantity' => 1,
                'unit_price' => $this->subscription->amount,
                'total' => $this->subscription->amount,
            ]
        ];
    }
}
