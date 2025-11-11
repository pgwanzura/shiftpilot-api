<?php

namespace App\Notifications;

use App\Models\Shift;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Shift $shift)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New Shift Request')
            ->line("A new shift has been requested by {$this->shift->employer->name}.")
            ->line("Location: {$this->shift->location->name}")
            ->line("Date: {$this->shift->start_time->format('l, M j, Y')}")
            ->line("Time: {$this->shift->start_time->format('g:i A')} - {$this->shift->end_time->format('g:i A')}")
            ->line("Position: {$this->shift->employee->position}")
            ->action('View Shift Request', url("/shifts/{$this->shift->id}"))
            ->line('Please assign a suitable candidate.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_requested',
            'shift_id' => $this->shift->id,
            'employer_name' => $this->shift->employer->name,
            'location' => $this->shift->location->name,
            'start_time' => $this->shift->start_time,
            'position' => $this->shift->employee->position,
            'message' => "New shift request from {$this->shift->employer->name} for {$this->shift->start_time->format('M j')}.",
            'url' => "/shifts/{$this->shift->id}",
        ];
    }
}
