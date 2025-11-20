<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LocationSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('locations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $locations = [];
        $now = Carbon::now();

        $employerLocations = [
            1 => ['Main Hospital', 'Outpatient Clinic', 'Community Health Centre'],
            2 => ['City Centre Store', 'Retail Park', 'Shopping Centre Unit'],
            3 => ['Central Depot', 'Distribution Centre', 'Logistics Hub'],
            4 => ['Head Office', 'Tech Campus', 'Development Centre'],
            5 => ['City Hotel', 'Conference Centre', 'Restaurant'],
            6 => ['Manufacturing Plant', 'Production Facility', 'Warehouse'],
            7 => ['Main Campus', 'Secondary Site', 'Primary School'],
            8 => ['Construction Site A', 'Construction Site B', 'Head Office']
        ];

        foreach ($employerLocations as $employerId => $locationNames) {
            $city = $this->getEmployerCity($employerId);
            foreach ($locationNames as $index => $name) {
                $locations[] = [
                    'employer_id' => $employerId,
                    'name' => $name,
                    'address_line1' => ($index + 1) . ' ' . $city . ' Road',
                    'city' => $city,
                    'postcode' => $this->generatePostcode($city),
                    'country' => 'GB',
                    'latitude' => 51.5074 + (rand(-500, 500) / 10000),
                    'longitude' => -0.1278 + (rand(-500, 500) / 10000),
                    'location_type' => $this->getLocationType($name),
                    'contact_name' => 'Manager ' . $employerId . '-' . ($index + 1),
                    'contact_phone' => '+44163299' . str_pad($employerId * 10 + $index, 3, '0', STR_PAD_LEFT),
                    'instructions' => 'Report to main reception',
                    'meta' => json_encode(['facilities' => ['parking', 'canteen', 'changing_rooms']]),
                    'created_at' => $now->copy()->subMonths(rand(6, 24)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('locations')->insert($locations);
        $this->command->info('Created ' . count($locations) . ' locations');
    }

    private function getEmployerCity($employerId): string
    {
        $cities = ['London', 'Manchester', 'Birmingham', 'London', 'Glasgow', 'Leeds', 'Manchester', 'Birmingham'];
        return $cities[$employerId - 1] ?? 'London';
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

    private function getLocationType(string $name): string
    {
        if (str_contains($name, 'Hospital') || str_contains($name, 'Clinic')) return 'healthcare';
        if (str_contains($name, 'Store') || str_contains($name, 'Retail')) return 'retail';
        if (str_contains($name, 'Depot') || str_contains($name, 'Warehouse')) return 'warehouse';
        if (str_contains($name, 'Office') || str_contains($name, 'Campus')) return 'office';
        if (str_contains($name, 'Hotel') || str_contains($name, 'Restaurant')) return 'hospitality';
        if (str_contains($name, 'Plant') || str_contains($name, 'Facility')) return 'manufacturing';
        if (str_contains($name, 'School') || str_contains($name, 'Campus')) return 'education';
        if (str_contains($name, 'Construction')) return 'construction';
        return 'other';
    }
}
