<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Shift $shift)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New Shift Assigned')
            ->line("You have been assigned to a new shift!")
            ->line("Location: {$this->shift->location->name}")
            ->line("Date: {$this->shift->start_time->format('l, M j, Y')}")
            ->line("Time: {$this->shift->start_time->format('g:i A')} - {$this->shift->end_time->format('g:i A')}")
            ->action('View Shift Details', url("/shifts/{$this->shift->id}"))
            ->line('Please confirm your availability.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_assigned',
            'shift_id' => $this->shift->id,
            'location' => $this->shift->location->name,
            'start_time' => $this->shift->start_time,
            'end_time' => $this->shift->end_time,
            'message' => "You have been assigned to a shift at {$this->shift->location->name} on {$this->shift->start_time->format('M j')}.",
            'url' => "/shifts/{$this->shift->id}",
        ];
    }
}
