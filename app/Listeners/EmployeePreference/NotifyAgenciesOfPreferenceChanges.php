<?php

namespace App\Listeners\EmployeePreference;

use App\Events\EmployeePreference\EmployeePreferencesUpdated;
use App\Models\AgencyEmployee;
use App\Notifications\EmployeePreference\EmployeePreferencesChangedNotification;

class NotifyAgenciesOfPreferenceChanges
{
    public function handle(EmployeePreferencesUpdated $event)
    {
        $relevantFields = [
            'preferred_locations',
            'max_travel_distance_km',
            'min_hourly_rate',
            'preferred_days',
            'preferred_start_times',
            'max_shifts_per_week'
        ];

        $hasRelevantChanges = collect($event->changes)
            ->keys()
            ->intersect($relevantFields)
            ->isNotEmpty();

        if ($hasRelevantChanges) {
            $agencies = AgencyEmployee::where('employee_id', $event->preferences->employee_id)
                ->where('status', 'active')
                ->with('agency')
                ->get();

            foreach ($agencies as $agencyEmployee) {
                $agencyEmployee->agency->notify(
                    new EmployeePreferencesChangedNotification($event->preferences, $event->changes)
                );
            }
        }
    }
}
