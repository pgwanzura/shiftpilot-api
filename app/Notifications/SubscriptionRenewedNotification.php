<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionRenewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Subscription $subscription)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Subscription Renewed Successfully')
            ->line("Your {$this->subscription->plan_name} subscription has been renewed successfully.")
            ->line("Amount: {$this->subscription->amount}")
            ->line("Billing Period: {$this->subscription->current_period_start->format('M j, Y')} - {$this->subscription->current_period_end->format('M j, Y')}")
            ->line("Next Billing Date: {$this->subscription->current_period_end->format('M j, Y')}")
            ->action('View Subscription', url("/subscriptions/{$this->subscription->id}"))
            ->line('Thank you for your continued business!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_renewed',
            'subscription_id' => $this->subscription->id,
            'plan_name' => $this->subscription->plan_name,
            'amount' => $this->subscription->amount,
            'interval' => $this->subscription->interval,
            'current_period_start' => $this->subscription->current_period_start,
            'current_period_end' => $this->subscription->current_period_end,
            'message' => "Your {$this->subscription->plan_name} subscription has been renewed.",
            'url' => "/subscriptions/{$this->subscription->id}",
        ];
    }
}
