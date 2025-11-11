<?php

namespace App\Notifications\Assignment;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Assignment $assignment) {}

    public function via($notifiable): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail($notifiable): MailMessage
    {
        $employerName = $this->assignment->contract->employer->name;
        $location = $this->assignment->location;

        return (new MailMessage)
            ->subject("🎯 New Assignment: {$this->assignment->role} at {$employerName}")
            ->greeting("Congratulations! You've Been Assigned")
            ->line("You have been assigned to a new position with the following details:")
            ->line("**Position:** {$this->assignment->role}")
            ->line("**Company:** {$employerName}")
            ->line("**Location:** {$location->name}")
            ->line("**Address:** {$location->address_line1}, {$location->city}")
            ->line("**Start Date:** {$this->assignment->start_date->format('l, F j, Y')}")
            ->line("**Assignment Type:** {$this->assignment->assignment_type->label()}")
            ->line("**Expected Hours:** {$this->assignment->expected_hours_per_week} hours/week")
            ->line("**Pay Rate:** £{$this->assignment->pay_rate}/hour")
            ->action('View Assignment Details', $this->getActionUrl())
            ->line('**Next Steps:**')
            ->line('• Review the assignment details')
            ->line('• Check your availability')
            ->line('• Contact your agency with any questions')
            ->line('• Prepare required documents if needed')
            ->salutation('Best regards,<br>ShiftPilot Team');
    }

    public function toSms($notifiable): string
    {
        $employerName = $this->assignment->contract->employer->name;
        return "New assignment: {$this->assignment->role} at {$employerName} starting {$this->assignment->start_date->format('M j')}. Check ShiftPilot for details.";
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'employee_assigned',
            'assignment_id' => $this->assignment->id,
            'role' => $this->assignment->role,
            'employer_name' => $this->assignment->contract->employer->name,
            'location_name' => $this->assignment->location->name,
            'start_date' => $this->assignment->start_date->format('Y-m-d'),
            'pay_rate' => $this->assignment->pay_rate,
            'message' => "You have been assigned as {$this->assignment->role} at {$this->assignment->contract->employer->name}",
            'action_url' => $this->getActionUrl(),
            'priority' => 'high',
            'requires_acknowledgment' => true
        ];
    }

    private function getActionUrl(): string
    {
        return config('app.url') . "/my-assignments/{$this->assignment->id}";
    }
}
