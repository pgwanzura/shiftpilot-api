<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Jobs\RecordPayment;
use App\Jobs\SchedulePayoutToAgency;
use App\Jobs\RecordPlatformFee;
use App\Services\WebhookService;
use App\Notifications\InvoicePaidNotification;

class ProcessInvoicePaid
{
    public function handle(InvoicePaid $event): void
    {
        RecordPayment::dispatch($event->invoice);

        SchedulePayoutToAgency::dispatch($event->invoice);

        RecordPlatformFee::dispatch($event->invoice);

        logger("Invoice paid: {$event->invoice->id}");

        WebhookService::dispatch('invoice.paid', [
            'invoice_id' => $event->invoice->id,
            'invoice_reference' => $event->invoice->reference,
            'amount' => $event->invoice->total_amount,
            'paid_at' => $event->invoice->paid_at->toISOString(),
            'payer_type' => $event->invoice->to_type,
            'payer_id' => $event->invoice->to_id,
        ]);
    }
}
