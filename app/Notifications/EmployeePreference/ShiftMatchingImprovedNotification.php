<?php

namespace App\Notifications\EmployeePreference;

use App\Models\Employee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShiftMatchingImprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $employee;
    public $matchingShiftsCount;

    public function __construct(Employee $employee, int $matchingShiftsCount)
    {
        $this->employee = $employee;
        $this->matchingShiftsCount = $matchingShiftsCount;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Better Shift Matches Available!')
            ->greeting('Hello ' . $notifiable->user->name . '!')
            ->line('Based on your recent preference updates, we found better shift matches for you!')
            ->line('**Matching shifts found:** ' . $this->matchingShiftsCount)
            ->action('View Available Shifts', url('/shifts/available'))
            ->line('Keep your preferences updated to get the best shift recommendations.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'shift_matching_improved',
            'employee_id' => $this->employee->id,
            'matching_shifts_count' => $this->matchingShiftsCount,
            'message' => 'We found ' . $this->matchingShiftsCount . ' shifts that match your preferences',
            'action_url' => '/shifts/available',
        ];
    }
}
