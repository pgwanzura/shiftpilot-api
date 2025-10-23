<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordPayment implements ShouldQueue
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
        Payment::create([
            'invoice_id' => $this->invoice->id,
            'payer_type' => $this->invoice->to_type,
            'payer_id' => $this->invoice->to_id,
            'amount' => $this->invoice->total_amount,
            'method' => 'stripe', // This would come from payment processor
            'processor_id' => 'pay_' . uniqid(),
            'status' => 'completed',
            'net_amount' => $this->invoice->total_amount,
        ]);

        // Update invoice status
        $this->invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        logger("Payment recorded for invoice: {$this->invoice->id}");
    }
}
