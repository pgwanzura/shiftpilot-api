<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AgencyBranchSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks before truncating
        Schema::disableForeignKeyConstraints();
        DB::table('agency_branches')->truncate();
        Schema::enableForeignKeyConstraints();

        $branches = [];
        $now = Carbon::now();

        $agencies = DB::table('agencies')->get();

        if ($agencies->isEmpty()) {
            $this->command->error('No agencies found. Please run AgencySeeder first.');
            return;
        }

        $londonAreas = [
            'Central London',
            'West End',
            'City',
            'Canary Wharf',
            'North London',
            'South London',
            'East London',
            'West London'
        ];

        foreach ($agencies as $agency) {
            $branchCount = rand(1, 3);

            for ($i = 1; $i <= $branchCount; $i++) {
                $area = $londonAreas[array_rand($londonAreas)];
                $isHeadOffice = $i === 1;

                $branches[] = [
                    'agency_id' => $agency->id,
                    'name' => "{$agency->name} - {$area}",
                    'branch_code' => strtoupper(substr($agency->name, 0, 3)) . $i,
                    'email' => "{$area}@{$this->slugify($agency->name)}.com",
                    'phone' => '+44123456' . rand(100, 999),
                    'address_line1' => rand(1, 100) . ' ' . $area . ' Road',
                    'city' => 'London',
                    'postcode' => $this->generateLondonPostcode(),
                    'country' => 'GB',
                    'contact_name' => "Branch Manager {$i}",
                    'contact_email' => "manager.{$area}@{$this->slugify($agency->name)}.com",
                    'contact_phone' => '+44123456' . rand(200, 899),
                    'status' => 'active',
                    'opening_hours' => json_encode([
                        'monday' => ['09:00', '17:00'],
                        'tuesday' => ['09:00', '17:00'],
                        'wednesday' => ['09:00', '17:00'],
                        'thursday' => ['09:00', '17:00'],
                        'friday' => ['09:00', '17:00'],
                        'saturday' => ['10:00', '14:00'],
                        'sunday' => 'closed'
                    ]),
                    'services_offered' => json_encode(['temporary_staffing', 'permanent_placement', 'contract_workers']),
                    'created_at' => $now->copy()->subMonths(rand(3, 18)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('agency_branches')->insert($branches);
        $this->command->info('Created ' . count($branches) . ' agency branches');
    }

    private function slugify($name)
    {
        return strtolower(str_replace(' ', '', $name));
    }

    private function generateLondonPostcode()
    {
        $areas = ['SW1', 'W1', 'WC2', 'EC1', 'N1', 'SE1', 'NW1'];
        return $areas[array_rand($areas)] . ' ' . rand(1, 9) . 'CD';
    }
}
