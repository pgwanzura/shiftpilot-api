<?php

namespace App\Services;

use App\Contracts\AvailabilityServiceInterface;
use App\Models\Employee;
use App\Models\EmployeeAvailability;
use App\Models\TimeOffRequest;
use App\Http\Requests\EmployeeAvailabilityRequest;
use App\Http\Requests\TimeOffRequestRequest;
use App\Events\AvailabilityUpdated;
use App\Events\TimeOffRequested;
use App\Events\TimeOffApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class EmployeeAvailabilityService implements AvailabilityServiceInterface
{
    public function __construct(
        private ConflictDetectionService $conflictService
    ) {}

    public function updateAvailability(Employee $employee, array $availabilityData): void
    {
        DB::transaction(function () use ($employee, $availabilityData) {
            $employee->employeeAvailabilities()->delete();

            $availabilities = collect($availabilityData)->map(function ($data) use ($employee) {
                return $this->createAvailabilityRecord($employee, $data);
            });

            event(new AvailabilityUpdated($employee, $availabilities));
        });
    }

    private function createAvailabilityRecord(Employee $employee, array $data): EmployeeAvailability
    {
        return EmployeeAvailability::create([
            'employee_id' => $employee->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'days_mask' => $data['days_mask'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'type' => $data['type'],
            'priority' => $data['priority'] ?? 5,
            'max_hours' => $data['max_hours'] ?? null,
            'flexible' => $data['flexible'] ?? false,
            'constraints' => $data['constraints'] ?? null,
        ]);
    }

    public function requestTimeOff(Employee $employee, array $timeOffData): TimeOffRequest
    {
        return DB::transaction(function () use ($employee, $timeOffData) {
            $timeOffRequest = TimeOffRequest::create([
                'employee_id' => $employee->id,
                'start_date' => $timeOffData['start_date'],
                'end_date' => $timeOffData['end_date'],
                'type' => $timeOffData['type'],
                'reason' => $timeOffData['reason'] ?? null,
                'status' => TimeOffRequest::STATUS_PENDING,
            ]);

            event(new TimeOffRequested($timeOffRequest));

            return $timeOffRequest;
        });
    }

    public function approveTimeOff(TimeOffRequest $timeOffRequest, int $approvedById): void
    {
        DB::transaction(function () use ($timeOffRequest, $approvedById) {
            $timeOffRequest->update([
                'status' => TimeOffRequest::STATUS_APPROVED,
                'approved_by_id' => $approvedById,
                'approved_at' => now(),
            ]);

            event(new TimeOffApproved($timeOffRequest));
        });
    }

    public function checkAvailabilityConflicts(Employee $employee, string $startDate, string $endDate): Collection
    {
        return $this->conflictService->findConflictingShifts($employee, $startDate, $endDate);
    }

    public function findAvailableEmployees(\DateTime $start, \DateTime $end): Collection
    {
        return EmployeeAvailability::forShift($start, $end)
            ->available()
            ->with(['employee' => function ($query) {
                $query->where('status', Employee::STATUS_ACTIVE);
            }])
            ->get()
            ->filter(function ($availability) use ($start, $end) {
                return $availability->canWorkShift($start, $end);
            })
            ->map(function ($availability) {
                return $availability->employee;
            })
            ->unique();
    }
}
