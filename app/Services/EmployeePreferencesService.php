<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeePreferences;
use App\Models\ShiftOffer;
use Illuminate\Support\Facades\DB;

class EmployeePreferencesService
{
    public function updatePreferences(Employee $employee, array $data): EmployeePreferences
    {
        return DB::transaction(function () use ($employee, $data) {
            $preferences = EmployeePreferences::createOrUpdateForEmployee($employee, $data);

            if (isset($data['notification_preferences'])) {
                $this->validateNotificationPreferences($data['notification_preferences']);
            }

            return $preferences;
        });
    }

    public function getPreferences(Employee $employee): ?EmployeePreferences
    {
        return EmployeePreferences::getForEmployee($employee);
    }

    public function shouldAutoAcceptOffer(ShiftOffer $offer): bool
    {
        $employee = $offer->agencyEmployee->employee;
        $preferences = $this->getPreferences($employee);

        if (!$preferences || !$preferences->auto_accept_offers) {
            return false;
        }

        $shiftData = [
            'hourly_rate' => $offer->shift->hourly_rate,
            'location_id' => $offer->shift->assignment->location_id,
            'role' => $offer->shift->assignment->role,
            'shift_type' => $offer->shift->assignment->assignment_type,
            'day_of_week' => $offer->shift->shift_date->dayName,
        ];

        return $preferences->meetsShiftCriteria($shiftData);
    }

    public function getMatchingEmployeesForShift(array $shiftCriteria): array
    {
        return EmployeePreferences::with('employee')
            ->where(function ($query) use ($shiftCriteria) {
                if (isset($shiftCriteria['hourly_rate'])) {
                    $query->where('min_hourly_rate', '<=', $shiftCriteria['hourly_rate'])
                        ->orWhereNull('min_hourly_rate');
                }

                if (isset($shiftCriteria['location_id'])) {
                    $query->whereJsonContains('preferred_locations', $shiftCriteria['location_id'])
                        ->orWhereNull('preferred_locations');
                }

                if (isset($shiftCriteria['role'])) {
                    $query->whereJsonContains('preferred_roles', $shiftCriteria['role'])
                        ->orWhereNull('preferred_roles');
                }
            })
            ->get()
            ->pluck('employee')
            ->filter()
            ->values()
            ->toArray();
    }

    private function validateNotificationPreferences(array $preferences): void
    {
        $validChannels = ['email', 'sms', 'in_app'];
        $validTypes = ['shift_offers', 'shift_updates', 'payroll', 'timesheet_approvals'];

        foreach ($preferences as $type => $channels) {
            if (!in_array($type, $validTypes)) {
                throw new \InvalidArgumentException("Invalid notification type: {$type}");
            }

            foreach ($channels as $channel) {
                if (!in_array($channel, $validChannels)) {
                    throw new \InvalidArgumentException("Invalid notification channel: {$channel}");
                }
            }
        }
    }
}
