<?php

namespace App\Notifications;

use App\Models\ShiftOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftOfferSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ShiftOffer $shiftOffer)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New Shift Offer')
            ->line("You have been offered a new shift!")
            ->line("Location: {$this->shiftOffer->shift->location->name}")
            ->line("Date: {$this->shiftOffer->shift->start_time->format('l, M j, Y')}")
            ->line("Time: {$this->shiftOffer->shift->start_time->format('g:i A')} - {$this->shiftOffer->shift->end_time->format('g:i A')}")
            ->line("Rate: {$this->shiftOffer->shift->hourly_rate}/hour")
            ->line("This offer expires on: {$this->shiftOffer->expires_at->format('M j, g:i A')}")
            ->action('Respond to Offer', url("/shift-offers/{$this->shiftOffer->id}"))
            ->line('Please respond before the offer expires.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_offer_sent',
            'shift_offer_id' => $this->shiftOffer->id,
            'shift_id' => $this->shiftOffer->shift_id,
            'location' => $this->shiftOffer->shift->location->name,
            'start_time' => $this->shiftOffer->shift->start_time,
            'hourly_rate' => $this->shiftOffer->shift->hourly_rate,
            'expires_at' => $this->shiftOffer->expires_at,
            'message' => "You have been offered a shift at {$this->shiftOffer->shift->location->name} on {$this->shiftOffer->shift->start_time->format('M j')}.",
            'url' => "/shift-offers/{$this->shiftOffer->id}",
        ];
    }
}
