<?php
namespace App\Notifications\Assignment;

use App\Models\Assignment;
use App\Enums\AssignmentStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Assignment $assignment,
        private AssignmentStatus $fromStatus,
        private AssignmentStatus $toStatus
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusIcon = $this->getStatusIcon();
        $employerName = $this->assignment->contract->employer->name;

        return (new MailMessage)
            ->subject("{$statusIcon} Assignment Status Updated: {$this->toStatus->label()}")
            ->greeting("Assignment Status Change")
            ->line("The assignment for **{$this->assignment->role}** at **{$employerName}** has been updated:")
            ->line("**Previous Status:** {$this->fromStatus->label()}")
            ->line("**Current Status:** {$this->toStatus->label()}")
            ->line("**Employee:** {$this->assignment->agencyEmployee->employee->user->name}")
            ->line("**Agency:** {$this->assignment->agencyEmployee->agency->name}")
            ->action('View Assignment Details', $this->getActionUrl())
            ->line($this->getStatusSpecificMessage())
            ->salutation('ShiftPilot Platform');
    }

    public function toSms($notifiable): string
    {
        $employeeName = $this->assignment->agencyEmployee->employee->user->name;
        $employerName = $this->assignment->contract->employer->name;

        return "Assignment update: {$this->assignment->role} at {$employerName} is now {$this->toStatus->label()}. Check your ShiftPilot account for details.";
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'assignment_status_changed',
            'assignment_id' => $this->assignment->id,
            'from_status' => $this->fromStatus->value,
            'to_status' => $this->toStatus->value,
            'role' => $this->assignment->role,
            'employer_name' => $this->assignment->contract->employer->name,
            'employee_name' => $this->assignment->agencyEmployee->employee->user->name,
            'message' => "Assignment status changed from {$this->fromStatus->label()} to {$this->toStatus->label()}",
            'action_url' => $this->getActionUrl(),
            'priority' => $this->getPriority(),
            'requires_action' => $this->requiresAction()
        ];
    }

    private function getStatusIcon(): string
    {
        return match ($this->toStatus) {
            AssignmentStatus::ACTIVE => '✅',
            AssignmentStatus::COMPLETED => '🏁',
            AssignmentStatus::CANCELLED => '❌',
            AssignmentStatus::SUSPENDED => '⏸️',
            AssignmentStatus::PENDING => '⏳',
            default => '📋'
        };
    }

    private function getStatusSpecificMessage(): string
    {
        return match ($this->toStatus) {
            AssignmentStatus::ACTIVE => 'The assignment is now active. Shifts can be scheduled and worked.',
            AssignmentStatus::COMPLETED => 'This assignment has been completed. Final invoices and payroll will be processed.',
            AssignmentStatus::CANCELLED => 'This assignment has been cancelled. Any future shifts have been automatically cancelled.',
            AssignmentStatus::SUSPENDED => 'This assignment is temporarily suspended. No new shifts can be scheduled until reactivated.',
            default => 'Please review the assignment details for more information.'
        };
    }

    private function getPriority(): string
    {
        return match ($this->toStatus) {
            AssignmentStatus::CANCELLED, AssignmentStatus::SUSPENDED => 'high',
            AssignmentStatus::ACTIVE, AssignmentStatus::COMPLETED => 'medium',
            default => 'low'
        };
    }

    private function requiresAction(): bool
    {
        return in_array($this->toStatus, [
            AssignmentStatus::SUSPENDED,
            AssignmentStatus::CANCELLED
        ]);
    }

    private function getActionUrl(): string
    {
        return config('app.url') . "/assignments/{$this->assignment->id}";
    }
}
