<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAvailability;
use App\Models\TimeOffRequest;
use App\Http\Requests\EmployeeAvailabilityRequest;
use App\Http\Requests\TimeOffRequestRequest;
use App\Events\AvailabilityUpdated;
use App\Events\TimeOffRequested;
use App\Events\TimeOffApproved;

class EmployeeAvailabilityService
{
    public function updateAvailability(Employee $employee, EmployeeAvailabilityRequest $request): void
    {
        // Clear existing availability and create new ones
        $employee->employeeAvailabilities()->delete();

        foreach ($request->availabilities as $availabilityData) {
            EmployeeAvailability::create(array_merge($availabilityData, [
                'employee_id' => $employee->id,
            ]));
        }

        event(new AvailabilityUpdated($employee));
    }

    public function requestTimeOff(Employee $employee, TimeOffRequestRequest $request): TimeOffRequest
    {
        $timeOffRequest = TimeOffRequest::create(array_merge($request->validated(), [
            'employee_id' => $employee->id,
            'status' => 'pending',
        ]));

        event(new TimeOffRequested($timeOffRequest));

        return $timeOffRequest;
    }

    public function approveTimeOff(TimeOffRequest $timeOffRequest, $approvedBy): void
    {
        $timeOffRequest->update([
            'status' => 'approved',
            'approved_by_id' => $approvedBy->id,
            'approved_at' => now(),
        ]);

        event(new TimeOffApproved($timeOffRequest));
    }

    public function checkAvailabilityConflicts(Employee $employee, $startDate, $endDate)
    {
        return $employee->shifts()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_time', [$startDate, $endDate])
                      ->orWhereBetween('end_time', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_time', '<=', $startDate)
                            ->where('end_time', '>=', $endDate);
                      });
            })
            ->whereIn('status', ['assigned', 'offered'])
            ->get();
    }
}
