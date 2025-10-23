<?php

// app/Listeners/SendInvoiceNotification.php

namespace App\Listeners;

use App\Events\InvoiceGenerated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendInvoiceNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private NotificationService $notificationService)
    {
    }

    public function handle(InvoiceGenerated $event): void
    {
        $invoice = $event->invoice;

        $this->notificationService->createNotification(
            $invoice->from,
            'invoice.generated',
            [
                'invoice_number' => $invoice->reference,
                'amount' => $invoice->total_amount,
                'due_date' => $invoice->due_date->format('Y-m-d'),
            ],
            ['email']
        );
    }
}
