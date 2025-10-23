<?php

namespace App\Notifications;

use App\Models\Payout;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayoutProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payout $payout)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Payout Processed')
            ->line("Your payout for the period {$this->payout->period_start->format('M j')} - {$this->payout->period_end->format('M j, Y')} has been processed.")
            ->line("Amount: {$this->payout->total_amount}")
            ->line("It should appear in your account within 2-3 business days.")
            ->action('View Payout Details', url("/payouts/{$this->payout->id}"))
            ->line('Thank you for your work!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payout_processed',
            'payout_id' => $this->payout->id,
            'amount' => $this->payout->total_amount,
            'period_start' => $this->payout->period_start,
            'period_end' => $this->payout->period_end,
            'message' => "Payout of {$this->payout->total_amount} has been processed for period {$this->payout->period_start->format('M j')} - {$this->payout->period_end->format('M j, Y')}.",
            'url' => "/payouts/{$this->payout->id}",
        ];
    }
}
