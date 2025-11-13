<?php

namespace App\Listeners\EmployeePreference;

use App\Events\EmployeePreference\EmployeePreferencesUpdated;
use App\Jobs\UpdateShiftMatchingForEmployee;
use App\Services\EmployeeMatchingService;

class UpdateEmployeeMatchingCriteria
{
    protected $matchingService;

    public function __construct(EmployeeMatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    public function handle(EmployeePreferencesUpdated $event)
    {
        $relevantFields = [
            'preferred_shift_types',
            'preferred_locations',
            'preferred_industries',
            'preferred_roles',
            'max_travel_distance_km',
            'min_hourly_rate',
            'preferred_shift_lengths',
            'preferred_days',
            'preferred_start_times',
            'preferred_employment_types',
            'max_shifts_per_week'
        ];

        $hasRelevantChanges = collect($event->changes)
            ->keys()
            ->intersect($relevantFields)
            ->isNotEmpty();

        if ($hasRelevantChanges) {
            UpdateShiftMatchingForEmployee::dispatch($event->preferences->employee_id);
        }
    }
}
