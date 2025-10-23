<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Timesheet;
use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function getPayrolls(array $filters = []): LengthAwarePaginator
    {
        $query = Payroll::with(['agency', 'employee.user']);

        if (isset($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (isset($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['period_start'])) {
            $query->where('period_start', '>=', $filters['period_start']);
        }

        if (isset($filters['period_end'])) {
            $query->where('period_end', '<=', $filters['period_end']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function markAsPaid(Payroll $payroll): Payroll
    {
        return DB::transaction(function () use ($payroll) {
            $payroll->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            return $payroll->fresh();
        });
    }

    public function processBatch(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $timesheets = Timesheet::whereHas('shift', function ($query) use ($data) {
                $query->where('agency_id', $data['agency_id']);
            })
            ->where('status', 'employer_approved')
            ->whereBetween('created_at', [$data['period_start'], $data['period_end']])
            ->get();

            $payrollRecords = [];

            foreach ($timesheets->groupBy('employee_id') as $employeeId => $employeeTimesheets) {
                $totalHours = $employeeTimesheets->sum('hours_worked');
                $employee = Employee::find($employeeId);

                $grossPay = $totalHours * $employee->pay_rate;
                $taxes = $grossPay * 0.20;
                $netPay = $grossPay - $taxes;

                $payroll = Payroll::create([
                    'agency_id' => $data['agency_id'],
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
}
