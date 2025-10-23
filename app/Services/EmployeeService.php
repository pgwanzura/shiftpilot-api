<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAvailability;
use App\Models\Payroll;
use App\Models\Shift;
use App\Models\ShiftOffer;
use App\Models\TimeOffRequest;
use App\Models\Timesheet;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public function __construct(
        private EmployeeAvailabilityService $availabilityService
    ) {
    }

    public function getDashboardStats(Employee $employee): array
    {
        return [
            'upcoming_shifts' => $employee->shifts()->whereIn('status', ['assigned'])->count(),
            'completed_shifts_count' => $employee->shifts()->where('status', 'completed')->count(),
            'total_earnings' => $employee->payroll()->where('status', 'paid')->sum('net_pay'),
            'pending_payments' => $employee->payroll()->where('status', 'unpaid')->sum('net_pay'),
            'pending_shift_offers' => $employee->shiftOffers()->where('status', 'pending')->count(),
        ];
    }

    public function clockIn(Shift $shift): Timesheet
    {
        return DB::transaction(function () use ($shift) {
            $timesheet = Timesheet::create([
                'shift_id' => $shift->id,
                'employee_id' => auth()->user()->employee->id,
                'clock_in' => now(),
                'status' => 'pending',
            ]);

            $shift->update(['status' => 'completed']);

            return $timesheet;
        });
    }

    public function clockOut(Shift $shift): Timesheet
    {
        return DB::transaction(function () use ($shift) {
            $timesheet = Timesheet::where('shift_id', $shift->id)
                ->where('employee_id', auth()->user()->employee->id)
                ->firstOrFail();

            $timesheet->update([
                'clock_out' => now(),
                'hours_worked' => $this->calculateHoursWorked($timesheet->clock_in, now()),
            ]);

            return $timesheet->fresh();
        });
    }

    public function getAvailability(Employee $employee)
    {
        return $employee->availability()->get();
    }

    public function getPayroll(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = $employee->payroll();

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function getShifts(Employee $employee, array $filters = []): LengthAwarePaginator
    {
        $query = $employee->shifts()->with(['employer', 'location']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function getTimesheets(Employee $employee): LengthAwarePaginator
    {
        return $employee->timesheets()->with(['shift'])->latest()->paginate(15);
    }

    public function getShiftOffers(Employee $employee): LengthAwarePaginator
    {
        return $employee->shiftOffers()
            ->with(['shift.employer', 'shift.location'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(15);
    }

    public function respondToShiftOffer(ShiftOffer $shiftOffer, bool $accept, ?string $notes): ShiftOffer
    {
        return DB::transaction(function () use ($shiftOffer, $accept, $notes) {
            $status = $accept ? 'accepted' : 'rejected';

            $shiftOffer->update([
                'status' => $status,
                'responded_at' => now(),
                'response_notes' => $notes,
            ]);

            if ($accept) {
                $shiftOffer->shift->update([
                    'employee_id' => $shiftOffer->employee_id,
                    'status' => 'assigned',
                ]);
            }

            return $shiftOffer->fresh();
        });
    }

    public function setAvailability(Employee $employee, array $data): EmployeeAvailability
    {
        return EmployeeAvailability::create(array_merge($data, [
            'employee_id' => $employee->id,
        ]));
    }

    public function submitTimeOffRequest(Employee $employee, array $data): TimeOffRequest
    {
        return $this->availabilityService->requestTimeOff($employee, $data);
    }

    public function updateAvailability(EmployeeAvailability $availability, array $data): EmployeeAvailability
    {
        $availability->update($data);
        return $availability->fresh();
    }

    private function calculateHoursWorked($clockIn, $clockOut): float
    {
        $diffInMinutes = $clockIn->diffInMinutes($clockOut);
        return round($diffInMinutes / 60, 2);
    }
}
