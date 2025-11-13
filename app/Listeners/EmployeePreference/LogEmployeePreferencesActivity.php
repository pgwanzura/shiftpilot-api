<?php

namespace App\Listeners\EmployeePreference;

use App\Events\EmployeePreference\EmployeePreferencesCreated;
use App\Events\EmployeePreference\EmployeePreferencesUpdated;
use App\Events\EmployeePreference\EmployeePreferencesDeleted;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class LogEmployeePreferencesActivity
{
    public function handle($event)
    {
        $logData = [];

        if ($event instanceof EmployeePreferencesCreated) {
            $action = 'employee_preferences_created';
            $description = 'Employee preferences created';
        } elseif ($event instanceof EmployeePreferencesUpdated) {
            $action = 'employee_preferences_updated';
            $description = 'Employee preferences updated';
            $logData['changes'] = $event->changes;
            $logData['original'] = $event->original;
        } elseif ($event instanceof EmployeePreferencesDeleted) {
            $action = 'employee_preferences_deleted';
            $description = 'Employee preferences deleted';
        } else {
            return;
        }

        AuditLog::create([
            'actor_type' => 'user',
            'actor_id' => Auth::id(),
            'action' => $action,
            'target_type' => 'employee_preferences',
            'target_id' => $event->preferences->id,
            'payload' => array_merge($logData, [
                'employee_id' => $event->preferences->employee_id,
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
