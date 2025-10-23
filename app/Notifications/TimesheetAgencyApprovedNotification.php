<?php

namespace App\Notifications;

use App\Models\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TimesheetAgencyApprovedNotification extends Notification implements ShouldQueue
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
            ->subject('Timesheet Ready for Final Approval')
            ->line("A timesheet has been approved by the agency and is ready for your final sign-off.")
            ->line("Employee: {$this->timesheet->employee->user->name}")
            ->line("Shift: {$this->timesheet->shift->start_time->format('M j, Y g:i A')} at {$this->timesheet->shift->location->name}")
            ->line("Hours: {$this->timesheet->hours_worked}")
            ->action('Approve Timesheet', url("/timesheets/{$this->timesheet->id}"))
            ->line('Please review and provide final approval.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'timesheet_agency_approved',
            'timesheet_id' => $this->timesheet->id,
            'employee_name' => $this->timesheet->employee->user->name,
            'shift_time' => $this->timesheet->shift->start_time,
            'location' => $this->timesheet->shift->location->name,
            'hours_worked' => $this->timesheet->hours_worked,
            'message' => "Timesheet for {$this->timesheet->employee->user->name} is ready for final approval.",
            'url' => "/timesheets/{$this->timesheet->id}",
        ];
    }
}
