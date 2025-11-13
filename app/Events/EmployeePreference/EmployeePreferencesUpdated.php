<?php

namespace App\Events\EmployeePreference;

use App\Models\EmployeePreferences;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmployeePreferencesUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $preferences;
    public $original;
    public $changes;

    public function __construct(EmployeePreferences $preferences, array $original, array $changes)
    {
        $this->preferences = $preferences;
        $this->original = $original;
        $this->changes = $changes;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('employee.preferences.' . $this->preferences->employee_id);
    }

    public function broadcastAs()
    {
        return 'employee.preferences.updated';
    }
}
