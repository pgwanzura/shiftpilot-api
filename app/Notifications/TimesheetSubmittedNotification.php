<?php

namespace App\Notifications;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimesheetSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Timesheet $timesheet)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Timesheet Submitted for Approval')
            ->line("A timesheet has been submitted and requires your approval.")
            ->line("Employee: {$this->timesheet->employee->user->name}")
            ->line("Shift: {$this->timesheet->shift->start_time->format('M j, Y g:i A')} at {$this->timesheet->shift->location->name}")
            ->line("Hours Worked: {$this->timesheet->hours_worked}")
            ->action('Review Timesheet', url("/timesheets/{$this->timesheet->id}"))
            ->line('Please review and approve the timesheet.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'timesheet_submitted',
            'timesheet_id' => $this->timesheet->id,
            'employee_name' => $this->timesheet->employee->user->name,
            'shift_time' => $this->timesheet->shift->start_time,
            'location' => $this->timesheet->shift->location->name,
            'hours_worked' => $this->timesheet->hours_worked,
            'message' => "Timesheet submitted by {$this->timesheet->employee->user->name} requires approval.",
            'url' => "/timesheets/{$this->timesheet->id}",
        ];
    }
}
