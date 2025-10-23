<?php

namespace App\Notifications;

use App\Models\Shift;
use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftOfferedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Shift $shift,
        public Employee $employee
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Shift Candidate Offered')
            ->line("A candidate has been offered for your shift.")
            ->line("Position: {$this->shift->employee->position}")
            ->line("Candidate: {$this->employee->user->name}")
            ->line("Shift: {$this->shift->start_time->format('M j, Y g:i A')} at {$this->shift->location->name}")
            ->action('Review Candidate', url("/shifts/{$this->shift->id}/offers"))
            ->line('Please review and approve the candidate.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shift_offered',
            'shift_id' => $this->shift->id,
            'employee_id' => $this->employee->id,
            'employee_name' => $this->employee->user->name,
            'location' => $this->shift->location->name,
            'start_time' => $this->shift->start_time,
            'message' => "{$this->employee->user->name} has been offered for a shift at {$this->shift->location->name}.",
            'url' => "/shifts/{$this->shift->id}/offers",
        ];
    }
}
