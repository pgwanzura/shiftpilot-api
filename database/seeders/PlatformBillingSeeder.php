<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PlatformBillingSeeder extends Seeder
{
    public function run()
    {
        // Check if table exists before proceeding
        if (!Schema::hasTable('platform_billings')) {
            $this->command->warn('Platform billings table does not exist. Skipping seeder.');
            return;
        }

        // Disable foreign key checks before truncating
        Schema::disableForeignKeyConstraints();
        DB::table('platform_billings')->truncate();
        Schema::enableForeignKeyConstraints();

        $billings = [];
        $now = Carbon::now();

        $agencies = DB::table('agencies')->get();
        $subscriptions = DB::table('subscriptions')->get();

        if ($agencies->isEmpty()) {
            $this->command->warn('No agencies found. Skipping platform billings.');
            return;
        }

        foreach ($agencies as $agency) {
            $billingCount = rand(1, 6); // 1-6 months of billing history

            for ($i = 0; $i < $billingCount; $i++) {
                $periodDate = $now->copy()->subMonths($i);
                $periodStart = $periodDate->copy()->startOfMonth();
                $periodEnd = $periodDate->copy()->endOfMonth();

                $subscriptionFee = rand(5000, 20000) / 100; // £50-£200
                $usageFee = rand(1000, 10000) / 100; // £10-£100
                $transactionFee = rand(500, 5000) / 100; // £5-£50
                $totalAmount = $subscriptionFee + $usageFee + $transactionFee;

                $status = ['draft', 'generated', 'sent', 'paid', 'overdue'][rand(0, 4)];

                $generatedAt = $periodEnd->copy()->addDays(rand(1, 3));
                $sentAt = $status !== 'draft' ? $generatedAt->copy()->addDays(rand(1, 5)) : null;
                $paidAt = in_array($status, ['paid']) ? $sentAt->copy()->addDays(rand(1, 14)) : null;
                $dueDate = $sentAt ? $sentAt->copy()->addDays(30) : null;

                $billings[] = [
                    'agency_id' => $agency->id,
                    'subscription_id' => $subscriptions->isNotEmpty() ? $subscriptions->random()->id : null,
                    'billing_period' => $periodDate->format('Y-m'),
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'subscription_fee' => $subscriptionFee,
                    'usage_fee' => $usageFee,
                    'transaction_fee' => $transactionFee,
                    'total_amount' => $totalAmount,
                    'currency' => 'GBP',
                    'status' => $status,
                    'generated_at' => $generatedAt,
                    'sent_at' => $sentAt,
                    'paid_at' => $paidAt,
                    'due_date' => $dueDate,
                    'invoice_number' => 'INV-' . $periodDate->format('Ym') . '-' . str_pad($agency->id, 4, '0', STR_PAD_LEFT),
                    'line_items' => json_encode([
                        ['description' => 'Monthly Subscription', 'amount' => $subscriptionFee],
                        ['description' => 'Platform Usage Fee', 'amount' => $usageFee],
                        ['description' => 'Transaction Fees', 'amount' => $transactionFee],
                    ]),
                    'notes' => $status === 'overdue' ? 'Payment overdue - reminder sent' : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('platform_billings')->insert($billings);
        $this->command->info('Created ' . count($billings) . ' platform billing records');

        // Show distribution
        $statusCounts = DB::table('platform_billings')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Platform billing status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }
    }
}
