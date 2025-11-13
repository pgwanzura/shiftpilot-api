<?php

namespace App\Notifications\EmployeePreference;

use App\Models\EmployeePreferences;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeePreferencesChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $preferences;
    public $changes;

    public function __construct(EmployeePreferences $preferences, array $changes)
    {
        $this->preferences = $preferences;
        $this->changes = $changes;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Employee Preferences Updated')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('An employee in your agency has updated their work preferences.')
            ->line('**Employee:** ' . $this->preferences->employee->user->name)
            ->line('**Changes:**')
            ->lines(collect($this->changes)->map(function ($value, $key) {
                return "• " . str_replace('_', ' ', $key) . ": " . (is_array($value) ? json_encode($value) : $value);
            })->toArray())
            ->action('View Employee Profile', url('/employees/' . $this->preferences->employee_id))
            ->line('These changes may affect shift matching and assignment recommendations.');
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'employee_preferences_changed',
            'employee_id' => $this->preferences->employee_id,
            'employee_name' => $this->preferences->employee->user->name,
            'changes' => $this->changes,
            'message' => 'Employee work preferences have been updated',
            'action_url' => '/employees/' . $this->preferences->employee_id,
        ];
    }
}
