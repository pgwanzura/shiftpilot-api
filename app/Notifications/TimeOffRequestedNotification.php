<?php

namespace App\Notifications;

use App\Models\TimeOffRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimeOffRequestedNotification extends Notification implements ShouldQueue
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
            ->subject('New Time Off Request')
            ->line("{$this->timeOffRequest->employee->user->name} has requested time off.")
            ->line("Type: " . ucfirst($this->timeOffRequest->type))
            ->line("Period: {$this->timeOffRequest->start_date->format('M j, Y')} - {$this->timeOffRequest->end_date->format('M j, Y')}")
            ->line("Reason: {$this->timeOffRequest->reason}")
            ->action('Review Request', url("/time-off/{$this->timeOffRequest->id}"))
            ->line('Please review and approve or deny the request.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'time_off_requested',
            'time_off_request_id' => $this->timeOffRequest->id,
            'employee_id' => $this->timeOffRequest->employee_id,
            'employee_name' => $this->timeOffRequest->employee->user->name,
            'request_type' => $this->timeOffRequest->type,
            'start_date' => $this->timeOffRequest->start_date,
            'end_date' => $this->timeOffRequest->end_date,
            'message' => "{$this->timeOffRequest->employee->user->name} has requested {$this->timeOffRequest->type} time off.",
            'url' => "/time-off/{$this->timeOffRequest->id}",
        ];
    }
}
