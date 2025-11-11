<?php

namespace App\Notifications;

use App\Models\ShiftOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftOfferAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ShiftOffer $shiftOffer)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Shift Offer Accepted')
            ->line("Great news! {$this->shiftOffer->employee->user->name} has accepted the shift offer.")
            ->line("Shift: {$this->shiftOffer->shift->start_time->format('M j, Y g:i A')} at {$this->shiftOffer->shift->location->name}")
            ->action('View Shift', url("/shifts/{$this->shiftOffer->shift_id}"))
            ->line('The shift has been confirmed and assigned.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_offer_accepted',
            'shift_offer_id' => $this->shiftOffer->id,
            'shift_id' => $this->shiftOffer->shift_id,
            'employee_id' => $this->shiftOffer->employee_id,
            'employee_name' => $this->shiftOffer->employee->user->name,
            'message' => "{$this->shiftOffer->employee->user->name} has accepted the shift offer.",
            'url' => "/shifts/{$this->shiftOffer->shift_id}",
        ];
    }
}
