<?php

namespace App\Notifications;

use App\Models\ShiftOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftOfferRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ShiftOffer $shiftOffer)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Shift Offer Declined')
            ->line("{$this->shiftOffer->employee->user->name} has declined the shift offer.")
            ->line("Shift: {$this->shiftOffer->shift->start_time->format('M j, Y g:i A')} at {$this->shiftOffer->shift->location->name}")
            ->line("Reason: " . ($this->shiftOffer->response_notes ?? 'No reason provided'))
            ->action('Find Another Candidate', url("/shifts/{$this->shiftOffer->shift_id}/candidates"))
            ->line('Please consider other available candidates.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'shift_offer_rejected',
            'shift_offer_id' => $this->shiftOffer->id,
            'shift_id' => $this->shiftOffer->shift_id,
            'employee_id' => $this->shiftOffer->employee_id,
            'employee_name' => $this->shiftOffer->employee->user->name,
            'reason' => $this->shiftOffer->response_notes,
            'message' => "{$this->shiftOffer->employee->user->name} has declined the shift offer.",
            'url' => "/shifts/{$this->shiftOffer->shift_id}/candidates",
        ];
    }
}
