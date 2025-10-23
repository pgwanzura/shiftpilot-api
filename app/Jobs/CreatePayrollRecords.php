<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreatePayrollRecords implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Payout $payout)
    {
    }

    public function handle(): void
    {
        $employees = $this->getEmployeesForPayout();

        foreach ($employees as $employee) {
            $payrollData = $this->calculatePayrollForEmployee($employee);

            Payroll::create([
                'agency_id' => $this->payout->agency_id,
                'employee_id' => $employee->id,
                'period_start' => $this->payout->period_start,
                'period_end' => $this->payout->period_end,
                'total_hours' => $payrollData['total_hours'],
                'gross_pay' => $payrollData['gross_pay'],
                'taxes' => $payrollData['taxes'],
                'net_pay' => $payrollData['net_pay'],
                'payout_id' => $this->payout->id,
            ]);
        }

        logger("Payroll records created for payout: {$this->payout->id}");
    }

    private function getEmployeesForPayout()
    {
        return $this->payout->agency->employees()
            ->whereHas('timesheets', function ($query) {
                $query->where('status', 'employer_approved')
                      ->whereBetween('created_at', [
                          $this->payout->period_start,
                          $this->payout->period_end
                      ]);
            })
            ->get();
    }

    private function calculatePayrollForEmployee($employee): array
    {
        $timesheets = $employee->timesheets()
            ->where('status', 'employer_approved')
            ->whereBetween('created_at', [
                $this->payout->period_start,
                $this->payout->period_end
            ])
            ->get();

        $totalHours = $timesheets->sum('hours_worked');
        $grossPay = $timesheets->sum(function ($timesheet) {
            return $timesheet->hours_worked * $timesheet->shift->hourly_rate;
        });

        $taxes = $grossPay * 0.20; // Simplified tax calculation
        $netPay = $grossPay - $taxes;

        return [
            'total_hours' => $totalHours,
            'gross_pay' => $grossPay,
            'taxes' => $taxes,
            'net_pay' => $netPay,
        ];
    }
}
