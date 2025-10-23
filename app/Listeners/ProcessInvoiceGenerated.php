<?php

namespace App\Listeners;

use App\Events\InvoiceGenerated;
use App\Jobs\CreateInvoiceLines;
use App\Jobs\ApplyTaxes;
use App\Jobs\SendInvoice;
use App\Services\WebhookService;
use App\Notifications\InvoiceGeneratedNotification;

class ProcessInvoiceGenerated
{
    public function handle(InvoiceGenerated $event): void
    {
        CreateInvoiceLines::dispatch($event->invoice);

        ApplyTaxes::dispatch($event->invoice);

        SendInvoice::dispatch($event->invoice);

        logger("Invoice generated: {$event->invoice->id}");

        WebhookService::dispatch('invoice.generated', [
            'invoice_id' => $event->invoice->id,
            'invoice_reference' => $event->invoice->reference,
            'amount' => $event->invoice->total_amount,
            'due_date' => $event->invoice->due_date->toISOString(),
            'from_type' => $event->invoice->from_type,
            'from_id' => $event->invoice->from_id,
            'to_type' => $event->invoice->to_type,
            'to_id' => $event->invoice->to_id,
            'generated_at' => now()->toISOString(),
        ]);
    }
}
