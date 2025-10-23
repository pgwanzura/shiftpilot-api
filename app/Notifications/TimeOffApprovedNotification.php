<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeOffApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TimeOffRequest $timeOffRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Time Off Request Approved')
            ->line("Your time off request has been approved!")
            ->line("Type: " . ucfirst($this->timeOffRequest->type))
            ->line("Period: {$this->timeOffRequest->start_date->format('M j, Y')} - {$this->timeOffRequest->end_date->format('M j, Y')}")
            ->line("Status: Approved")
            ->action('View Request', url("/time-off/{$this->timeOffRequest->id}"))
            ->line('Enjoy your time off!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'time_off_approved',
            'time_off_request_id' => $this->timeOffRequest->id,
            'request_type' => $this->timeOffRequest->type,
            'start_date' => $this->timeOffRequest->start_date,
            'end_date' => $this->timeOffRequest->end_date,
            'message' => "Your {$this->timeOffRequest->type} time off request has been approved.",
            'url' => "/time-off/{$this->timeOffRequest->id}",
        ];
    }
}
