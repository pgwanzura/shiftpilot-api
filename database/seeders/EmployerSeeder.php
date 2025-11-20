<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployerSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('employers')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $employers = [];
        $now = Carbon::now();

        $employerData = [
            ['St. Mary\'s Hospital NHS Trust', 'Healthcare', 'London'],
            ['Metro Retail Group PLC', 'Retail', 'Manchester'],
            ['City Logistics Ltd', 'Logistics', 'Birmingham'],
            ['Tech Solutions International', 'Technology', 'London'],
            ['Hospitality First Group', 'Hospitality', 'Glasgow'],
            ['Manufacturing Partners Co', 'Manufacturing', 'Leeds'],
            ['Education First Academy Trust', 'Education', 'Manchester'],
            ['Construction Masters Ltd', 'Construction', 'Birmingham']
        ];

        foreach ($employerData as $index => $data) {
            $employers[] = [
                'name' => $data[0],
                'legal_name' => $data[0],
                'billing_email' => 'finance@' . strtolower(str_replace([' ', '\'', 'PLC', 'Ltd', 'Trust', 'Group', 'Co'], '', $data[0])) . '.com',
                'phone' => '+441632980' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'address_line1' => rand(1, 100) . ' ' . $data[2] . ' Road',
                'city' => $data[2],
                'postcode' => $this->generatePostcode($data[2]),
                'country' => 'GB',
                'industry' => $data[1],
                'company_size' => ['1-50', '51-200', '201-500', '501-1000', '1000+'][array_rand([0, 1, 2, 3, 4])],
                'status' => 'active',
                'meta' => json_encode([
                    'established' => rand(1990, 2015),
                    'website' => 'https://' . strtolower(str_replace([' ', '\'', 'PLC', 'Ltd', 'Trust'], '', $data[0])) . '.com'
                ]),
                'created_at' => $now->copy()->subMonths(rand(12, 36)),
                'updated_at' => $now,
            ];
        }

        DB::table('employers')->insert($employers);
        $this->command->info('Created ' . count($employers) . ' employers');
    }

    private function generatePostcode(string $city): string
    {
        $prefixes = [
            'London' => ['E1', 'W1', 'SW1', 'NW1', 'SE1'],
            'Manchester' => ['M1', 'M2', 'M3', 'M4'],
            'Birmingham' => ['B1', 'B2', 'B3', 'B4'],
            'Glasgow' => ['G1', 'G2', 'G3', 'G4'],
            'Leeds' => ['LS1', 'LS2', 'LS3', 'LS4']
        ];

        $prefix = $prefixes[$city][array_rand($prefixes[$city])] ?? 'AB1';
        return $prefix . ' ' . rand(1, 9) . 'AB';
    }
}
