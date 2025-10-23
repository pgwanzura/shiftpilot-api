<?php

namespace App\Jobs;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendInvoice implements ShouldQueue
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
        // Send invoice via email to the recipient
        $recipient = $this->invoice->to;

        if ($recipient && method_exists($recipient, 'notify')) {
            $recipient->notify(new \App\Notifications\InvoiceGeneratedNotification($this->invoice));
        }

        logger("Invoice sent to recipient: {$this->invoice->id}");
    }
}
