<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\PlatformBilling;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordPlatformFee implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function handle(): void
    {
        // Calculate platform commission
        $platformBilling = PlatformBilling::first();
        $commissionAmount = $this->invoice->total_amount * ($platformBilling->commission_rate / 100);

        // Create commission invoice for agency
        $commissionInvoice = \App\Models\Invoice::create([
            'type' => 'agency_to_shiftpilot',
            'from_type' => 'agency',
            'from_id' => $this->invoice->from_id,
            'to_type' => 'shiftpilot',
            'to_id' => 1, // Platform ID
            'reference' => 'COM-' . now()->format('Ymd-His'),
            'subtotal' => $commissionAmount,
            'total_amount' => $commissionAmount,
            'due_date' => now()->addDays(30),
            'status' => 'pending',
            'metadata' => [
                'original_invoice_id' => $this->invoice->id,
                'commission_rate' => $platformBilling->commission_rate,
            ],
        ]);

        logger("Platform fee recorded: {$commissionAmount} for invoice: {$this->invoice->id}");
    }
}
