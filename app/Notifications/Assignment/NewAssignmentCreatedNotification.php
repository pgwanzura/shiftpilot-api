<?php
// app/Notifications/NewAssignmentCreatedNotification.php

namespace App\Notifications\Assignment;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAssignmentCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Assignment $assignment) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $agencyName = $this->assignment->agencyEmployee->agency->name;
        $employerName = $this->assignment->contract->employer->name;
        $employeeName = $this->assignment->agencyEmployee->employee->user->name;

        return (new MailMessage)
            ->subject("🔄 New Assignment Created - {$this->assignment->role} at {$employerName}")
            ->greeting("New Assignment Notification")
            ->line("A new assignment has been created with the following details:")
            ->line("**Role:** {$this->assignment->role}")
            ->line("**Employee:** {$employeeName}")
            ->line("**Agency:** {$agencyName}")
            ->line("**Employer:** {$employerName}")
            ->line("**Start Date:** {$this->assignment->start_date->format('M j, Y')}")
            ->line("**Location:** {$this->assignment->location->name}")
            ->line("**Assignment Type:** {$this->assignment->assignment_type->label()}")
            ->action('Review Assignment', $this->getActionUrl())
            ->line('Please ensure all contract terms are compliant before proceeding.')
            ->salutation('ShiftPilot Platform');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'assignment_created',
            'assignment_id' => $this->assignment->id,
            'role' => $this->assignment->role,
            'agency_id' => $this->assignment->agencyEmployee->agency_id,
            'employer_id' => $this->assignment->contract->employer_id,
            'employee_id' => $this->assignment->agencyEmployee->employee_id,
            'start_date' => $this->assignment->start_date->format('Y-m-d'),
            'message' => "New assignment created for {$this->assignment->role} at {$this->assignment->contract->employer->name}",
            'action_url' => $this->getActionUrl(),
            'priority' => 'high'
        ];
    }

    private function getActionUrl(): string
    {
        return config('app.url') . "/assignments/{$this->assignment->id}";
    }
}
