<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAvailability;
use App\Models\TimeOffRequest;
use App\Enums\TimeOffRequestStatus;
use App\Events\TimeOff\TimeOffRejected;
use App\Events\AvailabilityUpdated;
use App\Events\TimeOff\TimeOffRequested;
use App\Events\TimeOff\TimeOffApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

class EmployeeAvailabilityService
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
                'status' => TimeOffRequestStatus::PENDING, // Use enum case
            ]);

            event(new TimeOffRequested($timeOffRequest));

            return $timeOffRequest;
        });
    }

    public function approveTimeOff(TimeOffRequest $timeOffRequest, int $approvedById): void
    {
        DB::transaction(function () use ($timeOffRequest, $approvedById) {
            $timeOffRequest->update([
                'status' => TimeOffRequestStatus::APPROVED, // Use enum case
                'approved_by_id' => $approvedById,
                'approved_at' => now(),
            ]);

            event(new TimeOffApproved($timeOffRequest));
        });
    }

    public function rejectTimeOff(TimeOffRequest $timeOffRequest, int $rejectedById): void
    {
        DB::transaction(function () use ($timeOffRequest, $rejectedById) {
            $timeOffRequest->update([
                'status' => TimeOffRequestStatus::REJECTED,
                'approved_by_id' => $rejectedById,
                'approved_at' => now(),
            ]);

            event(new TimeOffRejected($timeOffRequest));
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

    /**
     * Check if an employee is available during a specific time period
     */
    public function isEmployeeAvailable(int $employeeId, string $startTime, string $endTime): bool
    {
        $employee = Employee::findOrFail($employeeId);
        $conflicts = $this->conflictService->findConflictingShifts($employee, $startTime, $endTime);

        return $conflicts->isEmpty();
    }

    /**
     * Get available employees for a time period
     */
    public function getAvailableEmployees(string $startTime, string $endTime): array
    {
        $start = new \DateTime($startTime);
        $end = new \DateTime($endTime);

        return $this->findAvailableEmployees($start, $end)->all();
    }

    /**
     * Check for scheduling conflicts
     */
    public function checkForConflicts(int $employeeId, string $startTime, string $endTime, ?int $excludeEventId = null): array
    {
        $employee = Employee::findOrFail($employeeId);
        $conflicts = $this->conflictService->findConflictingShifts($employee, $startTime, $endTime);

        return $conflicts->toArray();
    }

    /**
     * Get time off requests by status
     */
    public function getTimeOffRequestsByStatus(TimeOffRequestStatus $status): Collection
    {
        return TimeOffRequest::where('status', $status)->get();
    }

    /**
     * Check if time off request can be approved
     */
    public function canApproveTimeOff(TimeOffRequest $timeOffRequest): bool
    {
        return $timeOffRequest->status->canBeApproved();
    }

    /**
     * Check if time off request can be rejected
     */
    public function canRejectTimeOff(TimeOffRequest $timeOffRequest): bool
    {
        return $timeOffRequest->status->canBeRejected();
    }

    /**
     * Get pending time off requests for an employee
     */
    public function getPendingTimeOffRequests(Employee $employee): Collection
    {
        return TimeOffRequest::where('employee_id', $employee->id)
            ->where('status', TimeOffRequestStatus::PENDING)
            ->get();
    }
}
