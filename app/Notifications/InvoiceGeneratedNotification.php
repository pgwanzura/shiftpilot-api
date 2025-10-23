<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification implements ShouldQueue
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
            ->subject("Invoice #{$this->invoice->reference} Generated")
            ->line("A new invoice has been generated for your recent shifts.")
            ->line("Amount: {$this->invoice->total_amount}")
            ->line("Due Date: {$this->invoice->due_date->format('M j, Y')}")
            ->action('View Invoice', url("/invoices/{$this->invoice->id}"))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_generated',
            'invoice_id' => $this->invoice->id,
            'invoice_reference' => $this->invoice->reference,
            'amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date,
            'message' => "Invoice #{$this->invoice->reference} has been generated.",
            'url' => "/invoices/{$this->invoice->id}",
        ];
    }
}
