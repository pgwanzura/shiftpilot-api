<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('payrolls')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $payrolls = [];
        $now = Carbon::now();

        for ($month = 1; $month <= 2; $month++) {
            $periodStart = $now->copy()->subMonths($month)->startOfMonth();
            $periodEnd = $periodStart->copy()->endOfMonth();

            $activeAgencyEmployees = DB::table('agency_employees')
                ->where('status', 'active')
                ->inRandomOrder()
                ->limit(50)
                ->get();

            foreach ($activeAgencyEmployees as $agencyEmployee) {
                $totalHours = rand(80, 180);
                $grossPay = round($totalHours * $agencyEmployee->pay_rate, 2);
                $taxes = round($grossPay * 0.2, 2);
                $netPay = $grossPay - $taxes;

                $payrolls[] = [
                    'agency_employee_id' => $agencyEmployee->id,
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d'),
                    'total_hours' => $totalHours,
                    'gross_pay' => $grossPay,
                    'taxes' => $taxes,
                    'net_pay' => $netPay,
                    'status' => 'paid',
                    'paid_at' => $periodEnd->copy()->addDays(rand(5, 10)),
                    'payout_id' => null,
                    'created_at' => $periodEnd,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('payrolls')->insert($payrolls);
        $this->command->info('Created ' . count($payrolls) . ' payroll records');
    }
}
