<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAvailability;
use App\Models\EmployeePreferences;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\ShiftOffer;
use App\Models\TimeOffRequest;
use App\Models\Timesheet;
use App\Enums\ShiftStatus;
use App\Enums\TimesheetStatus;
use App\Enums\ShiftOfferStatus;
use App\Enums\TimeOffRequestStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeService
{
    public function __construct(
        private EmployeeAvailabilityService $availabilityService
    ) {}

    public function getDashboardStats(Employee $employee): array
    {
        return DB::transaction(function () use ($employee) {
            $thirtyDaysAgo = now()->subDays(30);

            return [
                'upcoming_shifts_count' => $employee->shifts()
                    ->whereIn('status', [ShiftStatus::SCHEDULED, ShiftStatus::APPROVED])
                    ->where('start_time', '>=', now())
                    ->count(),
                'completed_shifts_count' => $employee->shifts()
                    ->where('status', ShiftStatus::COMPLETED)
                    ->where('start_time', '>=', $thirtyDaysAgo)
                    ->count(),
                'total_earnings' => $employee->payrolls()
                    ->where('status', 'paid')
                    ->where('period_start', '>=', $thirtyDaysAgo)
                    ->sum('net_pay'),
                'pending_payments' => $employee->payrolls()
                    ->where('status', 'pending')
                    ->sum('net_pay'),
                'pending_shift_offers' => $employee->shiftOffers()
                    ->where('status', ShiftOfferStatus::PENDING)
                    ->where('expires_at', '>', now())
                    ->count(),
                'active_assignments' => $employee->assignments()
                    ->where('status', 'active')
                    ->count(),
            ];
        });
    }

    public function clockIn(Shift $shift, Employee $employee): Timesheet
    {
        if ($shift->employee_id !== $employee->id) {
            throw new \InvalidArgumentException('Employee does not own this shift');
        }

        if (!$shift->status->canBeStarted()) {
            throw new \InvalidArgumentException('Shift cannot be started in current status');
        }

        if ($shift->start_time->gt(now()->addMinutes(30))) {
            throw new \InvalidArgumentException('Cannot clock in more than 30 minutes before shift start');
        }

        return DB::transaction(function () use ($shift, $employee) {
            $existingTimesheet = Timesheet::where('shift_id', $shift->id)->first();
            if ($existingTimesheet) {
                throw new \InvalidArgumentException('Timesheet already exists for this shift');
            }

            $timesheet = Timesheet::create([
                'shift_id' => $shift->id,
                'clock_in' => now(),
                'status' => TimesheetStatus::PENDING,
            ]);

            $shift->update(['status' => ShiftStatus::IN_PROGRESS]);

            return $timesheet;
        });
    }

    public function clockOut(Shift $shift, Employee $employee): Timesheet
    {
        if ($shift->employee_id !== $employee->id) {
            throw new \InvalidArgumentException('Employee does not own this shift');
        }

        if ($shift->status !== ShiftStatus::IN_PROGRESS) {
            throw new \InvalidArgumentException('Shift is not in progress');
        }

        return DB::transaction(function () use ($shift, $employee) {
            $timesheet = Timesheet::where('shift_id', $shift->id)->firstOrFail();

            if ($timesheet->clock_out) {
                throw new \InvalidArgumentException('Already clocked out for this shift');
            }

            $clockOut = now();
            $hoursWorked = $this->calculateHoursWorked($timesheet->clock_in, $clockOut);

            if ($hoursWorked > 24) {
                throw new \InvalidArgumentException('Shift duration exceeds 24 hours');
            }

            $timesheet->update([
                'clock_out' => $clockOut,
                'hours_worked' => $hoursWorked,
            ]);

            $shift->update(['status' => ShiftStatus::COMPLETED]);

            return $timesheet->fresh();
        });
    }

    public function getAvailability(Employee $employee): LengthAwarePaginator
    {
        return $employee->employeeAvailabilities()
            ->where('end_date', '>=', now())
            ->orWhereNull('end_date')
            ->orderBy('start_date')
            ->paginate(20);
    }

    public function getPayrollHistory(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = $employee->payrolls()->with(['agencyEmployee.agency']);

        if (isset($filters['period_start'])) {
            $query->where('period_start', '>=', $filters['period_start']);
        }

        if (isset($filters['period_end'])) {
            $query->where('period_end', '<=', $filters['period_end']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('period_start', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getShifts(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = $employee->shifts()->with(['assignment.location', 'assignment.employer']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        if (isset($filters['upcoming'])) {
            $query->where('start_time', '>=', now());
        }

        return $query->orderBy('start_time', $filters['order'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getTimesheets(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = $employee->timesheets()->with(['shift.assignment']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getShiftOffers(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = $employee->shiftOffers()
            ->with(['shift.assignment.location', 'shift.assignment.employer'])
            ->where('expires_at', '>', now());

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->where('status', ShiftOfferStatus::PENDING);
        }

        return $query->orderBy('expires_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function respondToShiftOffer(ShiftOffer $shiftOffer, Employee $employee, bool $accept, ?string $notes = null): ShiftOffer
    {
        if ($shiftOffer->agency_employee_id !== $employee->agencyEmployees->pluck('id')->first()) {
            throw new \InvalidArgumentException('Employee does not own this shift offer');
        }

        if ($shiftOffer->status !== ShiftOfferStatus::PENDING) {
            throw new \InvalidArgumentException('Shift offer is no longer pending');
        }

        if ($shiftOffer->expires_at->lt(now())) {
            throw new \InvalidArgumentException('Shift offer has expired');
        }

        return DB::transaction(function () use ($shiftOffer, $accept, $notes) {
            $status = $accept ? ShiftOfferStatus::ACCEPTED : ShiftOfferStatus::REJECTED;

            $shiftOffer->update([
                'status' => $status,
                'responded_at' => now(),
                'response_notes' => $notes,
            ]);

            if ($accept) {
                $shiftOffer->shift->update([
                    'status' => ShiftStatus::SCHEDULED,
                ]);
            }

            return $shiftOffer->fresh();
        });
    }

    public function setAvailability(Employee $employee, array $data): EmployeeAvailability
    {
        if ($data['start_date']->lt(now())) {
            throw new \InvalidArgumentException('Availability start date cannot be in the past');
        }

        if (isset($data['end_date']) && $data['end_date']->lte($data['start_date'])) {
            throw new \InvalidArgumentException('Availability end date must be after start date');
        }

        return EmployeeAvailability::create(array_merge($data, [
            'employee_id' => $employee->id,
        ]));
    }

    public function updateAvailability(EmployeeAvailability $availability, Employee $employee, array $data): EmployeeAvailability
    {
        if ($availability->employee_id !== $employee->id) {
            throw new \InvalidArgumentException('Employee does not own this availability');
        }

        if (isset($data['start_date']) && $data['start_date']->lt(now())) {
            throw new \InvalidArgumentException('Availability start date cannot be in the past');
        }

        $availability->update($data);
        return $availability->fresh();
    }

    public function deleteAvailability(EmployeeAvailability $availability, Employee $employee): bool
    {
        if ($availability->employee_id !== $employee->id) {
            throw new \InvalidArgumentException('Employee does not own this availability');
        }

        return $availability->delete();
    }

    public function submitTimeOffRequest(Employee $employee, array $data): TimeOffRequest
    {
        if ($data['start_date']->lt(now())) {
            throw new \InvalidArgumentException('Time off start date cannot be in the past');
        }

        if ($data['end_date']->lt($data['start_date'])) {
            throw new \InvalidArgumentException('Time off end date must be after start date');
        }

        $overlappingRequests = $employee->timeOffRequests()
            ->where('status', TimeOffRequestStatus::PENDING)
            ->where(function ($query) use ($data) {
                $query->whereBetween('start_date', [$data['start_date'], $data['end_date']])
                    ->orWhereBetween('end_date', [$data['start_date'], $data['end_date']])
                    ->orWhere(function ($q) use ($data) {
                        $q->where('start_date', '<=', $data['start_date'])
                            ->where('end_date', '>=', $data['end_date']);
                    });
            })
            ->exists();

        if ($overlappingRequests) {
            throw new \InvalidArgumentException('Overlapping time off request already exists');
        }

        return TimeOffRequest::create(array_merge($data, [
            'employee_id' => $employee->id,
            'status' => TimeOffRequestStatus::PENDING,
        ]));
    }

    public function updatePreferences(Employee $employee, array $data): EmployeePreferences
    {
        $preferences = $employee->preferences()->firstOrNew([]);

        if (isset($data['min_hourly_rate']) && $data['min_hourly_rate'] < 0) {
            throw new \InvalidArgumentException('Minimum hourly rate cannot be negative');
        }

        if (isset($data['max_travel_distance_km']) && $data['max_travel_distance_km'] < 0) {
            throw new \InvalidArgumentException('Travel distance cannot be negative');
        }

        $preferences->fill($data);
        $preferences->save();

        return $preferences;
    }

    public function getCurrentAssignments(Employee $employee): LengthAwarePaginator
    {
        return $employee->assignments()
            ->with(['location', 'employer', 'contract.agency'])
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('start_date')
            ->paginate(10);
    }

    private function calculateHoursWorked(Carbon $clockIn, Carbon $clockOut): float
    {
        $diffInMinutes = $clockIn->diffInMinutes($clockOut);
        return round($diffInMinutes / 60, 2);
    }
}
