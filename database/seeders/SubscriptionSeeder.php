<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('subscriptions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $subscriptions = [];
        $now = Carbon::now();

        $pricePlans = DB::table('price_plans')->get()->keyBy('id');

        if ($pricePlans->isEmpty()) {
            $this->command->error('No price plans found. Please run PricePlanSeeder first.');
            return;
        }

        $agencies = DB::table('agencies')->get();

        foreach ($agencies as $agency) {
            $plan = $pricePlans->where('type', 'agency')->first();

            if (!$plan) {
                $this->command->warn("No agency price plan found for agency {$agency->id}");
                continue;
            }

            $startedAt = $now->copy()->subMonths(rand(6, 18));

            $subscriptions[] = [
                'agency_id' => $agency->id,
                'price_plan_id' => $plan->id,
                'amount' => $plan->amount,
                'interval' => $plan->billing_interval,
                'status' => 'active',
                'started_at' => $startedAt,
                'current_period_start' => $now->copy()->startOfMonth(),
                'current_period_end' => $now->copy()->addMonth()->startOfMonth(),
                'meta' => json_encode([
                    'billing_cycle' => $plan->billing_interval,
                    'payment_method' => 'direct_debit',
                    'auto_renew' => true,
                    'plan_features' => $plan->features ?? []
                ]),
                'created_at' => $startedAt,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($subscriptions, 50) as $chunk) {
            DB::table('subscriptions')->insert($chunk);
        }

        $this->command->info('Created ' . count($subscriptions) . ' subscriptions');
        $this->debugSubscriptionDistribution();
    }

    private function debugSubscriptionDistribution(): void
    {
        $statusCounts = DB::table('subscriptions')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Subscription status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        $intervalCounts = DB::table('subscriptions')
            ->select('interval', DB::raw('count(*) as count'))
            ->groupBy('interval')
            ->get();

        $this->command->info('Billing interval distribution:');
        foreach ($intervalCounts as $count) {
            $this->command->info("  {$count->interval}: {$count->count}");
        }

        $planCounts = DB::table('subscriptions')
            ->join('price_plans', 'subscriptions.price_plan_id', '=', 'price_plans.id')
            ->select('price_plans.name', DB::raw('count(*) as count'))
            ->groupBy('price_plans.name')
            ->get();

        $this->command->info('Price plan distribution:');
        foreach ($planCounts as $count) {
            $this->command->info("  {$count->name}: {$count->count}");
        }

        $amountStats = DB::table('subscriptions')
            ->selectRaw('SUM(amount) as total_revenue, AVG(amount) as average_amount')
            ->first();

        $this->command->info("Financial summary:");
        $this->command->info("  Total monthly revenue: £" . number_format($amountStats->total_revenue, 2));
        $this->command->info("  Average subscription: £" . number_format($amountStats->average_amount, 2));

        $activeAgencies = DB::table('subscriptions')
            ->where('status', 'active')
            ->count();

        $totalAgencies = DB::table('agencies')->count();

        $this->command->info("Agency coverage: {$activeAgencies}/{$totalAgencies} agencies have active subscriptions");
    }
}
