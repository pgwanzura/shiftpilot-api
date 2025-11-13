<?php

namespace App\Services;

use App\Models\EmployeePreferences;
use App\Models\ShiftRequest;
use Illuminate\Support\Facades\DB;

class EmployeeMatchingService
{
    public function findMatchingShifts(EmployeePreferences $preferences): array
    {
        $query = ShiftRequest::where('status', 'published')
            ->with(['location', 'employer'])
            ->where('start_date', '>=', now());

        if (!empty($preferences->preferred_locations)) {
            $query->whereHas('location', function ($q) use ($preferences) {
                $q->whereIn('city', $preferences->preferred_locations)
                    ->orWhereIn('county', $preferences->preferred_locations);
            });
        }

        if (!empty($preferences->preferred_industries)) {
            $query->whereHas('employer', function ($q) use ($preferences) {
                $q->whereIn('industry', $preferences->preferred_industries);
            });
        }

        if (!empty($preferences->preferred_roles)) {
            $query->whereIn('role', $preferences->preferred_roles);
        }

        if ($preferences->min_hourly_rate) {
            $query->where('max_hourly_rate', '>=', $preferences->min_hourly_rate);
        }

        if (!empty($preferences->preferred_shift_types)) {
            $query->whereIn('shift_pattern', $preferences->preferred_shift_types);
        }

        return $query->orderBy('start_date')
            ->limit(50)
            ->get()
            ->toArray();
    }

    public function calculateMatchScore(EmployeePreferences $preferences, ShiftRequest $shiftRequest): float
    {
        $score = 0;
        $totalWeights = 0;

        $criteria = [
            'location' => [
                'weight' => 0.3,
                'match' => $this->checkLocationMatch($preferences, $shiftRequest)
            ],
            'rate' => [
                'weight' => 0.25,
                'match' => $this->checkRateMatch($preferences, $shiftRequest)
            ],
            'role' => [
                'weight' => 0.2,
                'match' => $this->checkRoleMatch($preferences, $shiftRequest)
            ],
            'schedule' => [
                'weight' => 0.15,
                'match' => $this->checkScheduleMatch($preferences, $shiftRequest)
            ],
            'industry' => [
                'weight' => 0.1,
                'match' => $this->checkIndustryMatch($preferences, $shiftRequest)
            ],
        ];

        foreach ($criteria as $criterion) {
            $score += $criterion['weight'] * $criterion['match'];
            $totalWeights += $criterion['weight'];
        }

        return $totalWeights > 0 ? $score / $totalWeights : 0;
    }

    private function checkLocationMatch(EmployeePreferences $preferences, ShiftRequest $shiftRequest): float
    {
        if (empty($preferences->preferred_locations)) {
            return 0.5;
        }

        $location = $shiftRequest->location;

        if (in_array($location->city, $preferences->preferred_locations)) {
            return 1.0;
        }

        if (in_array($location->county, $preferences->preferred_locations)) {
            return 0.8;
        }

        return 0.2;
    }

    private function checkRateMatch(EmployeePreferences $preferences, ShiftRequest $shiftRequest): float
    {
        if (!$preferences->min_hourly_rate) {
            return 0.5;
        }

        $shiftRate = (float) $shiftRequest->max_hourly_rate;
        $minRate = (float) $preferences->min_hourly_rate;

        if ($shiftRate >= $minRate * 1.2) {
            return 1.0;
        }

        if ($shiftRate >= $minRate) {
            return 0.8;
        }

        return 0.1;
    }

    private function checkRoleMatch(EmployeePreferences $preferences, ShiftRequest $shiftRequest): float
    {
        if (empty($preferences->preferred_roles)) {
            return 0.5;
        }

        return in_array($shiftRequest->role, $preferences->preferred_roles) ? 1.0 : 0.2;
    }

    private function checkScheduleMatch(EmployeePreferences $preferences, ShiftRequest $shiftRequest): float
    {
        if (empty($preferences->preferred_days)) {
            return 0.5;
        }

        $shiftDay = strtolower($shiftRequest->start_date->format('l'));

        return in_array($shiftDay, $preferences->preferred_days) ? 1.0 : 0.3;
    }

    private function checkIndustryMatch(EmployeePreferences $preferences, ShiftRequest $shiftRequest): float
    {
        if (empty($preferences->preferred_industries)) {
            return 0.5;
        }

        $employerIndustry = $shiftRequest->employer->industry;

        return in_array($employerIndustry, $preferences->preferred_industries) ? 1.0 : 0.2;
    }
}
