<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\EmployerAgencyLink;
use App\Models\Invoice;
use App\Models\Payroll;
use App\Models\Payout;
use App\Models\Placement;
use App\Models\Shift;
use App\Models\ShiftOffer;
use App\Models\ShiftTemplate;
use App\Models\Subscription;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AgencyService
{
    public function getDashboardStats(Agency $agency): array
    {
        return [
            'total_employees' => $agency->employees()->count(),
            'total_placements' => $agency->placements()->count(),
            'active_placements' => $agency->placements()->where('status', 'active')->count(),
            'pending_timesheets' => $agency->timesheets()->where('status', 'pending')->count(),
            'total_shifts' => $agency->shifts()->count(),
            'active_shifts' => $agency->shifts()->whereIn('status', ['open', 'offered', 'assigned'])->count(),
            'completed_shifts' => $agency->shifts()->where('status', 'completed')->count(),
            'pending_invoices' => $agency->invoices()->where('status', 'pending')->count(),
            'total_revenue' => $agency->invoices()->where('status', 'paid')->sum('total_amount'),
            'upcoming_payouts' => $agency->payouts()->where('status', 'processing')->sum('total_amount'),
        ];
    }

    public function approveTimesheet(Timesheet $timesheet): Timesheet
    {
        return DB::transaction(function () use ($timesheet) {
            $timesheet->update([
                'status' => 'agency_approved',
                'agency_approved_by' => auth()->id(),
                'agency_approved_at' => now(),
            ]);

            event(new \App\Events\TimesheetAgencyApproved($timesheet));

            return $timesheet->fresh();
        });
    }

    public function createAgent(Agency $agency, array $data): User
    {
        return DB::transaction(function () use ($agency, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password'] ?? 'password'),
                'role' => 'agent',
                'status' => 'active',
            ]);

            $agency->agents()->create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'permissions' => $data['permissions'] ?? [],
            ]);

            return $user;
        });
    }

    public function createPlacement(Agency $agency, array $data): Placement
    {
        return DB::transaction(function () use ($agency, $data) {
            return Placement::create([
                'employee_id' => $data['employee_id'],
                'employer_id' => $data['employer_id'],
                'agency_id' => $agency->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'employee_rate' => $data['employee_rate'] ?? null,
                'client_rate' => $data['client_rate'] ?? null,
                'status' => 'active',
            ]);
        });
    }

    public function createShiftFromTemplate(ShiftTemplate $template, string $date): Shift
    {
        return DB::transaction(function () use ($template, $date) {
            $startTime = \Carbon\Carbon::parse($date . ' ' . $template->start_time);
            $endTime = \Carbon\Carbon::parse($date . ' ' . $template->end_time);

            return Shift::create([
                'employer_id' => $template->employer_id,
                'agency_id' => auth()->user()->agency->id,
                'location_id' => $template->location_id,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hourly_rate' => $template->hourly_rate,
                'status' => 'open',
                'created_by_type' => 'agency',
                'created_by_id' => auth()->id(),
                'meta' => [
                    'created_from_template' => $template->id,
                    'role_requirement' => $template->role_requirement,
                ]
            ]);
        });
    }

    public function getAvailableEmployees(Agency $agency)
    {
        // Get employees who are available for new placements
        return $agency->employees()
            ->where('status', 'active')
            ->whereDoesntHave('placements', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['user'])
            ->get();
    }

    public function getContacts(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = Contact::whereHas('employer', function ($q) use ($agency) {
            $q->whereHas('agencyLinks', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id)->where('status', 'approved');
            });
        })->with(['employer', 'user']);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getEmployees(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->employees()->with(['user', 'employer']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getEmployerLinks(Agency $agency): LengthAwarePaginator
    {
        return $agency->employerLinks()->with(['employer'])->paginate(15);
    }

    public function getInvoices(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->invoices()->with(['from', 'to']);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getPayroll(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->payroll()->with(['employee.user']);

        if (isset($filters['period_start'])) {
            $query->where('period_start', '>=', $filters['period_start']);
        }

        if (isset($filters['period_end'])) {
            $query->where('period_end', '<=', $filters['period_end']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getPayouts(Agency $agency): LengthAwarePaginator
    {
        return $agency->payouts()->with(['payroll'])->paginate(15);
    }

    public function getPlacements(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->placements()->with(['employee.user', 'employer']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getShifts(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->shifts()->with(['employer', 'location', 'employee.user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getSubscriptions(Agency $agency)
    {
        return $agency->subscriptions;
    }

    public function getTimesheets(Agency $agency, array $filters = []): LengthAwarePaginator
    {
        $query = $agency->timesheets()->with(['shift', 'employee.user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function processPayroll(Agency $agency, array $data): array
    {
        return DB::transaction(function () use ($agency, $data) {
            // Get approved timesheets for the period
            $timesheets = Timesheet::whereHas('shift', function ($q) use ($agency) {
                $q->where('agency_id', $agency->id);
            })
            ->where('status', 'employer_approved')
            ->whereBetween('created_at', [$data['period_start'], $data['period_end']])
            ->get();

            $payrollRecords = [];

            foreach ($timesheets->groupBy('employee_id') as $employeeId => $employeeTimesheets) {
                $totalHours = $employeeTimesheets->sum('hours_worked');
                $employee = Employee::find($employeeId);

                $grossPay = $totalHours * $employee->pay_rate;
                $taxes = $grossPay * 0.20; // Simplified tax calculation
                $netPay = $grossPay - $taxes;

                $payroll = Payroll::create([
                    'agency_id' => $agency->id,
                    'employee_id' => $employeeId,
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                    'total_hours' => $totalHours,
                    'gross_pay' => $grossPay,
                    'taxes' => $taxes,
                    'net_pay' => $netPay,
                    'status' => 'unpaid',
                ]);

                $payrollRecords[] = $payroll;
            }

            return $payrollRecords;
        });
    }

    public function offerEmployeeForShift(Agency $agency, $shiftId, $employeeId): ShiftOffer
    {
        return DB::transaction(function () use ($agency, $shiftId, $employeeId) {
            $shift = Shift::findOrFail($shiftId);
            $employee = Employee::findOrFail($employeeId);

            // Verify the employee belongs to the agency
            if ($employee->agency_id !== $agency->id) {
                throw new \Exception('Employee does not belong to this agency');
            }

            return ShiftOffer::create([
                'shift_id' => $shiftId,
                'employee_id' => $employeeId,
                'offered_by_id' => auth()->id(),
                'status' => 'pending',
                'expires_at' => now()->addHours(24), // 24 hours to respond
            ]);
        });
    }

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        return $employee->fresh();
    }

    public function updatePlacement(Placement $placement, array $data): Placement
    {
        $placement->update($data);
        return $placement->fresh();
    }
}
