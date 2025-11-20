<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PricePlanSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('price_plans')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $plans = [];
        $now = Carbon::now();

        $planData = [
            [
                'plan_key' => 'agency_starter',
                'name' => 'Agency Starter',
                'description' => 'Perfect for small staffing agencies just getting started',
                'base_amount' => 99.00,
                'billing_interval' => 'monthly',
                'features' => ['basic_analytics', 'up_to_10_employees', 'email_support'],
                'limits' => ['employees' => 10, 'shifts_per_month' => 100],
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'plan_key' => 'agency_pro',
                'name' => 'Agency Pro',
                'description' => 'Advanced features for growing staffing agencies',
                'base_amount' => 199.00,
                'billing_interval' => 'monthly',
                'features' => ['advanced_analytics', 'unlimited_employees', 'priority_support', 'custom_reporting'],
                'limits' => ['employees' => 0, 'shifts_per_month' => 500],
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'plan_key' => 'agency_enterprise',
                'name' => 'Agency Enterprise',
                'description' => 'Full-featured solution for large staffing agencies',
                'base_amount' => 399.00,
                'billing_interval' => 'monthly',
                'features' => ['enterprise_analytics', 'unlimited_employees', '24_7_support', 'api_access', 'custom_integrations'],
                'limits' => ['employees' => 0, 'shifts_per_month' => 0],
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'plan_key' => 'employer_basic',
                'name' => 'Employer Basic',
                'description' => 'Essential features for small businesses',
                'base_amount' => 49.00,
                'billing_interval' => 'monthly',
                'features' => ['basic_shift_management', 'up_to_5_users', 'email_support'],
                'limits' => ['users' => 5, 'active_shifts' => 20],
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'plan_key' => 'employer_premium',
                'name' => 'Employer Premium',
                'description' => 'Advanced workforce management for growing businesses',
                'base_amount' => 149.00,
                'billing_interval' => 'monthly',
                'features' => ['advanced_scheduling', 'unlimited_users', 'priority_support', 'custom_approval_workflows'],
                'limits' => ['users' => 0, 'active_shifts' => 100],
                'is_active' => true,
                'sort_order' => 5
            ]
        ];

        foreach ($planData as $plan) {
            $plans[] = [
                'plan_key' => $plan['plan_key'],
                'name' => $plan['name'],
                'description' => $plan['description'],
                'base_amount' => $plan['base_amount'],
                'billing_interval' => $plan['billing_interval'],
                'features' => json_encode($plan['features']),
                'limits' => json_encode($plan['limits']),
                'is_active' => $plan['is_active'],
                'sort_order' => $plan['sort_order'],
                'meta' => json_encode(['popular' => $plan['plan_key'] === 'agency_pro']),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('price_plans')->insert($plans);
        $this->command->info('Created ' . count($plans) . ' price plans');
    }
}
