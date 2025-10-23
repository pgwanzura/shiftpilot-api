<?php

namespace App\Notifications;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimesheetEmployerApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Timesheet $timesheet)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Timesheet Fully Approved')
            ->line("The timesheet has been fully approved and is now ready for billing.")
            ->line("Employee: {$this->timesheet->employee->user->name}")
            ->line("Shift: {$this->timesheet->shift->start_time->format('M j, Y')} at {$this->timesheet->shift->location->name}")
            ->line("Hours: {$this->timesheet->hours_worked}")
            ->line("Total Amount: " . ($this->timesheet->hours_worked * $this->timesheet->shift->hourly_rate))
            ->action('View Timesheet', url("/timesheets/{$this->timesheet->id}"))
            ->line('An invoice will be generated shortly.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'timesheet_employer_approved',
            'timesheet_id' => $this->timesheet->id,
            'employee_name' => $this->timesheet->employee->user->name,
            'shift_time' => $this->timesheet->shift->start_time,
            'location' => $this->timesheet->shift->location->name,
            'hours_worked' => $this->timesheet->hours_worked,
            'message' => "Timesheet for {$this->timesheet->employee->user->name} has been fully approved.",
            'url' => "/timesheets/{$this->timesheet->id}",
        ];
    }
}
