<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeSeeder extends Seeder
{
    private array $qualifications = [
        ['name' => 'First Aid at Work', 'level' => 'Level 3'],
        ['name' => 'Food Hygiene Certificate', 'level' => 'Level 2'],
        ['name' => 'Manual Handling', 'level' => 'Certified'],
        ['name' => 'SIA License', 'level' => 'Security'],
        ['name' => 'CSCS Card', 'level' => 'Construction'],
        ['name' => 'Patient Care Certificate', 'level' => 'Healthcare'],
        ['name' => 'HGV Class 1 License', 'level' => 'Category C+E'],
        ['name' => 'Forklift License', 'level' => 'Counterbalance'],
        ['name' => 'Fire Safety', 'level' => 'Level 2'],
        ['name' => 'Safeguarding Adults', 'level' => 'Level 2']
    ];

    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('employees')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $employees = [];
        $now = Carbon::now();

        // Get employee users (users with role 'employee')
        $employeeUsers = DB::table('users')
            ->where('role', 'employee')
            ->orderBy('id')
            ->get();

        if ($employeeUsers->isEmpty()) {
            $this->command->error('No employee users found! Make sure UserSeeder runs first.');
            return;
        }

        $this->command->info("Found {$employeeUsers->count()} employee users to process");

        foreach ($employeeUsers as $index => $user) {
            $employees[] = [
                'user_id' => $user->id, // Use the actual user ID from the users table
                'national_insurance_number' => 'AB' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'date_of_birth' => $now->copy()->subYears(rand(18, 65))->subMonths(rand(0, 11))->format('Y-m-d'),
                'address_line1' => rand(1, 100) . ' Main Street',
                'city' => ['London', 'Manchester', 'Birmingham', 'Leeds', 'Glasgow'][array_rand([0, 1, 2, 3, 4])],
                'postcode' => $this->generatePostcode('London'),
                'country' => 'GB',
                'emergency_contact_name' => $this->generateFirstName() . ' ' . $this->generateLastName(),
                'emergency_contact_phone' => '+447' . rand(500000000, 799999999),
                'qualifications' => json_encode($this->getRandomQualifications()),
                'certifications' => json_encode($this->getRandomCertifications()),
                'status' => $user->status, // Use the status from the user
                'meta' => json_encode([
                    'preferred_shift_types' => ['day', 'evening', 'night'][array_rand([0, 1, 2])],
                    'max_travel_distance' => rand(10, 50)
                ]),
                'created_at' => $now->copy()->subMonths(rand(1, 24)),
                'updated_at' => $now,
            ];
        }

        DB::table('employees')->insert($employees);
        $this->command->info('Created ' . count($employees) . ' employees');
    }

    private function getRandomQualifications(): array
    {
        $count = rand(2, 5);
        $selected = array_rand($this->qualifications, $count);
        if (!is_array($selected)) $selected = [$selected];

        $result = [];
        foreach ($selected as $index) {
            $result[] = $this->qualifications[$index];
        }
        return $result;
    }

    private function getRandomCertifications(): array
    {
        $certifications = [
            ['name' => 'Health and Safety Certificate', 'issued' => '2023-01-15'],
            ['name' => 'Manual Handling Training', 'issued' => '2023-03-20'],
            ['name' => 'Fire Safety Training', 'issued' => '2023-05-10'],
            ['name' => 'Customer Service Excellence', 'issued' => '2023-07-05'],
        ];
        return array_slice($certifications, 0, rand(1, 3));
    }

    private function generateFirstName(): string
    {
        $names = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Elizabeth'];
        return $names[array_rand($names)];
    }

    private function generateLastName(): string
    {
        $names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
        return $names[array_rand($names)];
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
