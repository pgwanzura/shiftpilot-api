<?php

namespace Database\Seeders;

use App\Models\PricePlan;
use Illuminate\Database\Seeder;

class PricePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'plan_key' => 'agency_starter',
                'name' => 'Starter',
                'description' => 'Perfect for small agencies just getting started',
                'base_amount' => 49.00,
                'billing_interval' => 'monthly',
                'features' => ['basic_reporting', 'email_support', 'up_to_5_employees'],
                'limits' => ['employees' => 5, 'shifts_per_month' => 100],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'plan_key' => 'agency_professional',
                'name' => 'Professional',
                'description' => 'For growing agencies with expanding needs',
                'base_amount' => 99.00,
                'billing_interval' => 'monthly',
                'features' => ['advanced_reporting', 'phone_support', 'api_access', 'up_to_25_employees'],
                'limits' => ['employees' => 25, 'shifts_per_month' => 500],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'plan_key' => 'agency_enterprise',
                'name' => 'Enterprise',
                'description' => 'For large agencies with complex requirements',
                'base_amount' => 199.00,
                'billing_interval' => 'monthly',
                'features' => ['premium_reporting', 'dedicated_support', 'full_api_access', 'custom_integrations'],
                'limits' => ['employees' => 100, 'shifts_per_month' => 2000],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            PricePlan::create($plan);
        }
    }
}
