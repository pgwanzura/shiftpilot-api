<?php

namespace App\Events\EmployeePreference;

use App\Models\EmployeePreferences;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeePreferencesCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $preferences;

    public function __construct(EmployeePreferences $preferences)
    {
        $this->preferences = $preferences;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('employee.preferences.' . $this->preferences->employee_id);
    }

    public function broadcastAs()
    {
        return 'employee.preferences.created';
    }
}
