<?php

namespace App\Notifications;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvailabilityUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Employee $employee)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Employee Availability Updated')
            ->line("{$this->employee->user->name} has updated their availability.")
            ->action('View Availability', url("/employees/{$this->employee->id}/availability"))
            ->line('Please review the changes when you have a moment.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'availability_updated',
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->user->name,
            'message' => "{$this->employee->user->name} has updated their availability.",
            'url' => "/employees/{$this->employee->id}/availability",
        ];
    }
}
