<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $users = [];
        $now = Carbon::now();

        // 1. Super Admin
        $users[] = [
            'name' => 'System Administrator',
            'email' => 'admin@shiftpilot.com',
            'password' => Hash::make('Password123!'),
            'role' => 'super_admin',
            'phone' => '+441632960001',
            'status' => 'active',
            'email_verified_at' => $now,
            'last_login_at' => $now->copy()->subHours(12),
            'created_at' => $now->copy()->subYear(),
            'updated_at' => $now,
        ];

        // 2. Agency Admins (5 users)
        $agencyNames = ['Elite Staffing', 'Prime Workforce', 'Talent Connect', 'Professional Recruiters', 'Skilled Labor Partners'];
        foreach ($agencyNames as $index => $name) {
            $users[] = [
                'name' => $name . ' Manager',
                'email' => strtolower(str_replace(' ', '', $name)) . '@example.com',
                'password' => Hash::make('Password123!'),
                'role' => 'agency_admin',
                'phone' => '+4416329600' . (10 + $index),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->copy()->subDays(rand(1, 7)),
                'created_at' => $now->copy()->subMonths(rand(6, 12)),
                'updated_at' => $now,
            ];
        }

        // 3. Agents (15 users)
        for ($i = 1; $i <= 15; $i++) {
            $agencyId = ceil($i / 3);
            $users[] = [
                'name' => 'Agent ' . $i,
                'email' => 'agent' . $i . '@agency' . $agencyId . '.com',
                'password' => Hash::make('Password123!'),
                'role' => 'agent',
                'phone' => '+441632970' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->copy()->subDays(rand(1, 14)),
                'created_at' => $now->copy()->subMonths(rand(3, 9)),
                'updated_at' => $now,
            ];
        }

        // 4. Employer Admins (8 users)
        $employerNames = [
            'St. Mary\'s Hospital NHS Trust',
            'Metro Retail Group PLC',
            'City Logistics Ltd',
            'Tech Solutions International',
            'Hospitality First Group',
            'Manufacturing Partners Co',
            'Education First Academy Trust',
            'Construction Masters Ltd'
        ];

        foreach ($employerNames as $index => $name) {
            $users[] = [
                'name' => $name . ' Admin',
                'email' => 'admin@' . strtolower(str_replace([' ', '\'', 'PLC', 'Ltd', 'Trust'], '', $name)) . '.com',
                'password' => Hash::make('Password123!'),
                'role' => 'employer_admin',
                'phone' => '+441632980' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'status' => 'active',
                'email_verified_at' => $now,
                'last_login_at' => $now->copy()->subDays(rand(1, 10)),
                'created_at' => $now->copy()->subMonths(rand(4, 10)),
                'updated_at' => $now,
            ];
        }

        // 5. Contacts (2-4 per employer = ~24 users)
        $contactCount = 1;
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $numContacts = rand(2, 4);
            for ($j = 1; $j <= $numContacts; $j++) {
                $users[] = [
                    'name' => 'Contact ' . $contactCount,
                    'email' => 'contact' . $contactCount . '@employer' . $employerId . '.com',
                    'password' => Hash::make('Password123!'),
                    'role' => 'contact',
                    'phone' => '+441632990' . str_pad($contactCount, 3, '0', STR_PAD_LEFT),
                    'status' => 'active',
                    'email_verified_at' => $now,
                    'last_login_at' => $now->copy()->subDays(rand(5, 30)),
                    'created_at' => $now->copy()->subMonths(rand(2, 8)),
                    'updated_at' => $now,
                ];
                $contactCount++;
            }
        }

        // 6. Employees (100 users) - FIXED: Create exactly 100 employee users
        for ($i = 1; $i <= 100; $i++) {
            $firstName = $this->generateFirstName();
            $lastName = $this->generateLastName();
            $users[] = [
                'name' => $firstName . ' ' . $lastName,
                'email' => strtolower($firstName . '.' . $lastName . $i) . '@example.com',
                'password' => Hash::make('Password123!'),
                'role' => 'employee',
                'phone' => '+447' . rand(500000000, 799999999),
                'status' => rand(0, 10) > 1 ? 'active' : 'inactive',
                'email_verified_at' => $now,
                'last_login_at' => $now->copy()->subDays(rand(1, 60)),
                'created_at' => $now->copy()->subMonths(rand(1, 18)),
                'updated_at' => $now,
            ];
        }

        DB::table('users')->insert($users);
        $this->command->info('Created ' . count($users) . ' users');

        // Debug: Show user counts by role
        $this->debugUserCounts();
    }

    private function debugUserCounts()
    {
        $counts = DB::table('users')
            ->select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get();

        $this->command->info('User counts by role:');
        foreach ($counts as $count) {
            $this->command->info("  {$count->role}: {$count->count}");
        }
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
}
