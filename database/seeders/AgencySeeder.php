<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgencySeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('agencies')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $agencies = [];
        $now = Carbon::now();

        $agencyData = [
            ['Elite Staffing Solutions Ltd', 'COMP100001', 12.5],
            ['Prime Workforce Agency PLC', 'COMP100002', 11.8],
            ['Talent Connect UK Limited', 'COMP100003', 13.2],
            ['Professional Recruiters Group', 'COMP100004', 10.9],
            ['Skilled Labor Partners Ltd', 'COMP100005', 14.0]
        ];

        foreach ($agencyData as $index => $data) {
            $agencies[] = [
                'name' => $data[0],
                'legal_name' => $data[0],
                'registration_number' => $data[1],
                'billing_email' => 'accounts@' . strtolower(str_replace([' ', 'Ltd', 'PLC', 'Limited'], '', $data[0])) . '.com',
                'phone' => '+4416329600' . (10 + $index),
                'address_line1' => ($index + 1) . ' Business Park',
                'city' => 'London',
                'postcode' => 'E1 6AN',
                'country' => 'GB',
                'default_markup_percent' => $data[2],
                'subscription_status' => 'active',
                'meta' => json_encode([
                    'business_hours' => ['start' => '08:30', 'end' => '17:30'],
                    'specializations' => $this->getAgencySpecializations($index)
                ]),
                'created_at' => $now->copy()->subMonths(rand(12, 24)),
                'updated_at' => $now,
            ];
        }

        DB::table('agencies')->insert($agencies);
        $this->command->info('Created ' . count($agencies) . ' agencies');
    }

    private function getAgencySpecializations(int $index): array
    {
        $specializations = [
            ['Healthcare', 'Social Care'],
            ['Logistics', 'Warehousing'],
            ['Technology', 'Professional Services'],
            ['Hospitality', 'Retail'],
            ['Construction', 'Industrial']
        ];
        return $specializations[$index] ?? ['General Staffing'];
    }
}
