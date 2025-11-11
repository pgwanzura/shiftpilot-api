<?php

namespace App\Notifications\Assignment;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentCompletionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Assignment $assignment) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $duration = $this->assignment->duration_days;
        $employeeName = optional($this->assignment->agencyEmployee?->employee?->user)->name ?? 'N/A';
        $employerName = optional($this->assignment->contract?->employer)->name ?? 'N/A';
        $startDate = optional($this->assignment->start_date)?->format('M j, Y') ?? 'N/A';
        $endDate = optional($this->assignment->end_date)?->format('M j, Y') ?? 'Ongoing';

        return (new MailMessage)
            ->subject("🏁 Assignment Completed: {$this->assignment->role}")
            ->greeting('Assignment Successfully Completed')
            ->line('The following assignment has been marked as completed:')
            ->line("**Role:** {$this->assignment->role}")
            ->line("**Employee:** {$employeeName}")
            ->line("**Employer:** {$employerName}")
            ->line("**Duration:** {$duration} days")
            ->line("**Start Date:** {$startDate}")
            ->line("**End Date:** {$endDate}")
            ->action('View Assignment Summary', $this->getActionUrl())
            ->line('**Next Steps:**')
            ->line('• Final timesheets are being processed')
            ->line('• Invoices will be generated automatically')
            ->line('• Payroll will be processed according to schedule')
            ->line('• Performance feedback can be submitted')
            ->salutation('ShiftPilot Platform');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'assignment_completed',
            'assignment_id' => $this->assignment->id,
            'role' => $this->assignment->role,
            'employer_name' => optional($this->assignment->contract?->employer)->name ?? 'N/A',
            'employee_name' => optional($this->assignment->agencyEmployee?->employee?->user)->name ?? 'N/A',
            'duration_days' => $this->assignment->duration_days,
            'completion_date' => now()->format('Y-m-d'),
            'message' => "Assignment for {$this->assignment->role} at " . (optional($this->assignment->contract?->employer)->name ?? 'N/A') . " has been completed",
            'action_url' => $this->getActionUrl(),
            'priority' => 'medium',
        ];
    }

    private function getActionUrl(): string
    {
        return rtrim(config('app.url'), '/') . "/assignments/{$this->assignment->id}/summary";
    }
}
