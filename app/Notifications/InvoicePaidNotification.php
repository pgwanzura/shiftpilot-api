<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Invoice #{$this->invoice->reference} Paid")
            ->line("Invoice #{$this->invoice->reference} has been paid successfully.")
            ->line("Amount: {$this->invoice->total_amount}")
            ->line("Paid on: {$this->invoice->paid_at->format('M j, Y')}")
            ->action('View Receipt', url("/invoices/{$this->invoice->id}"))
            ->line('Thank you for your payment!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_paid',
            'invoice_id' => $this->invoice->id,
            'invoice_reference' => $this->invoice->reference,
            'amount' => $this->invoice->total_amount,
            'paid_at' => $this->invoice->paid_at,
            'message' => "Invoice #{$this->invoice->reference} has been paid.",
            'url' => "/invoices/{$this->invoice->id}",
        ];
    }
}
