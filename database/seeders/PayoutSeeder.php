<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayoutSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('payouts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $payouts = [];
        $now = Carbon::now();

        $agencies = DB::table('agencies')->get();

        if ($agencies->isEmpty()) {
            $this->command->warn('No agencies found. Please run AgencySeeder first.');
            return;
        }

        foreach ($agencies as $agency) {
            for ($month = 1; $month <= 3; $month++) {
                $periodStart = $now->copy()->subMonths($month)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();

                $payrollData = DB::table('payrolls')
                    ->join('agency_employees', 'payrolls.agency_employee_id', '=', 'agency_employees.id')
                    ->where('agency_employees.agency_id', $agency->id)
                    ->where('payrolls.period_start', '>=', $periodStart->format('Y-m-d'))
                    ->where('payrolls.period_end', '<=', $periodEnd->format('Y-m-d'))
                    ->select(
                        DB::raw('COALESCE(SUM(payrolls.gross_pay), 0) as total_amount'),
                        DB::raw('COUNT(DISTINCT payrolls.agency_employee_id) as employee_count')
                    )
                    ->first();

                $totalAmount = $payrollData->total_amount ?? 0;
                $employeeCount = $payrollData->employee_count ?? 0;

                if ($totalAmount > 0 && $employeeCount > 0) {
                    $invoiceCount = DB::table('invoices')
                        ->where('from_type', 'App\\Models\\Agency')
                        ->where('from_id', $agency->id)
                        ->where('due_date', '>=', $periodStart->format('Y-m-d'))
                        ->where('due_date', '<=', $periodEnd->format('Y-m-d'))
                        ->count();

                    $payouts[] = [
                        'agency_id' => $agency->id,
                        'period_start' => $periodStart->format('Y-m-d'),
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'total_amount' => $totalAmount,
                        'employee_count' => $employeeCount,
                        'status' => $this->getPayoutStatus($periodEnd),
                        'provider_payout_id' => 'po_' . strtoupper(\Illuminate\Support\Str::random(14)),
                        'metadata' => json_encode([
                            'period' => $periodStart->format('F Y'),
                            'invoice_count' => $invoiceCount,
                            'currency' => 'GBP',
                            'notes' => 'Automated payout processing'
                        ]),
                        'created_at' => $periodEnd->copy()->addDays(rand(1, 7)),
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (empty($payouts)) {
            $this->createFallbackPayouts($agencies, $now, $payouts);
        }

        foreach (array_chunk($payouts, 50) as $chunk) {
            DB::table('payouts')->insert($chunk);
        }

        $this->command->info('Created ' . count($payouts) . ' payouts');
        $this->debugPayoutDistribution();
    }

    private function getPayoutStatus(Carbon $periodEnd): string
    {
        $now = Carbon::now();
        if ($periodEnd->diffInDays($now) > 14) {
            return 'paid';
        } elseif ($periodEnd->diffInDays($now) > 7) {
            return 'processing';
        } else {
            return 'processing';
        }
    }

    private function createFallbackPayouts($agencies, $now, &$payouts): void
    {
        $this->command->warn('No payroll data found. Creating fallback payouts.');

        foreach ($agencies as $agency) {
            for ($month = 1; $month <= 2; $month++) {
                $periodStart = $now->copy()->subMonths($month)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();

                $employeeCount = DB::table('agency_employees')
                    ->where('agency_id', $agency->id)
                    ->count();

                if ($employeeCount > 0) {
                    $invoiceCount = DB::table('invoices')
                        ->where('from_type', 'App\\Models\\Agency')
                        ->where('from_id', $agency->id)
                        ->where('due_date', '>=', $periodStart->format('Y-m-d'))
                        ->where('due_date', '<=', $periodEnd->format('Y-m-d'))
                        ->count();

                    $totalAmount = $employeeCount * rand(150000, 450000) / 100;

                    $payouts[] = [
                        'agency_id' => $agency->id,
                        'period_start' => $periodStart->format('Y-m-d'),
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'total_amount' => $totalAmount,
                        'employee_count' => $employeeCount,
                        'status' => 'paid',
                        'provider_payout_id' => 'po_' . strtoupper(\Illuminate\Support\Str::random(14)),
                        'metadata' => json_encode([
                            'period' => $periodStart->format('F Y'),
                            'invoice_count' => $invoiceCount,
                            'currency' => 'GBP',
                            'fallback_data' => true,
                            'estimated_employees' => $employeeCount
                        ]),
                        'created_at' => $periodEnd->copy()->addDays(rand(1, 7)),
                        'updated_at' => $now,
                    ];
                }
            }
        }
    }

    private function debugPayoutDistribution(): void
    {
        $statusCounts = DB::table('payouts')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Payout status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        $amountStats = DB::table('payouts')
            ->selectRaw('COUNT(*) as total, SUM(total_amount) as total_amount, AVG(total_amount) as average_amount')
            ->first();

        if ($amountStats->total > 0) {
            $this->command->info("Payout financial summary:");
            $this->command->info("  Total payouts: {$amountStats->total}");
            $this->command->info("  Total amount: £" . number_format($amountStats->total_amount, 2));
            $this->command->info("  Average payout: £" . number_format($amountStats->average_amount, 2));

            $employeeStats = DB::table('payouts')
                ->selectRaw('AVG(employee_count) as avg_employees, MAX(employee_count) as max_employees')
                ->first();

            $this->command->info("Employee statistics:");
            $this->command->info("  Average employees per payout: " . number_format($employeeStats->avg_employees, 1));
            $this->command->info("  Maximum employees in payout: " . $employeeStats->max_employees);

            $agencyStats = DB::table('payouts')
                ->join('agencies', 'payouts.agency_id', '=', 'agencies.id')
                ->selectRaw('COUNT(DISTINCT agency_id) as agency_count')
                ->first();

            $this->command->info("Agency coverage: {$agencyStats->agency_count} agencies have payouts");
        }
    }
}
