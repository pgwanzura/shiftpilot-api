<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContactSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('contacts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $contacts = [];
        $now = Carbon::now();

        $contactUserId = 29; // After super admin (1) + agency admins (2-6) + agents (7-21) + employer admins (22-29)
        for ($employerId = 1; $employerId <= 8; $employerId++) {
            $numContacts = rand(2, 4);
            $roles = ['Operations Manager', 'HR Manager', 'Department Supervisor', 'Site Manager'];

            for ($j = 0; $j < $numContacts; $j++) {
                $contacts[] = [
                    'employer_id' => $employerId,
                    'user_id' => $contactUserId++,
                    'role' => $j === 0 ? 'manager' : ($j === 1 ? 'approver' : 'supervisor'),
                    'can_approve_timesheets' => $j !== 3,
                    'can_approve_assignments' => $j === 0,
                    'meta' => json_encode(['department' => $roles[$j]]),
                    'created_at' => $now->copy()->subMonths(rand(4, 12)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('contacts')->insert($contacts);
        $this->command->info('Created ' . count($contacts) . ' contacts');
    }
}
