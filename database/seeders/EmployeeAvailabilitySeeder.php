<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeAvailabilitySeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('employee_availabilities')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $availabilities = [];
        $now = Carbon::now();

        $patterns = [
            ['days' => 31, 'start' => '09:00', 'end' => '17:00', 'type' => 'preferred'], // Weekdays
            ['days' => 31, 'start' => '17:00', 'end' => '22:00', 'type' => 'available'], // Weekdays evening
            ['days' => 96, 'start' => '08:00', 'end' => '16:00', 'type' => 'available'], // Weekends
            ['days' => 127, 'start' => '06:00', 'end' => '18:00', 'type' => 'available'], // All week
        ];

        for ($employeeId = 1; $employeeId <= 100; $employeeId++) {
            $numPatterns = rand(1, 2);
            $selectedPatterns = array_rand($patterns, $numPatterns);
            if (!is_array($selectedPatterns)) $selectedPatterns = [$selectedPatterns];

            foreach ($selectedPatterns as $patternIndex) {
                $pattern = $patterns[$patternIndex];

                $availabilities[] = [
                    'employee_id' => $employeeId,
                    'start_date' => $now->copy()->subMonths(1)->format('Y-m-d'),
                    'end_date' => $now->copy()->addMonths(6)->format('Y-m-d'),
                    'days_mask' => $pattern['days'],
                    'start_time' => $pattern['start'],
                    'end_time' => $pattern['end'],
                    'type' => $pattern['type'],
                    'priority' => $pattern['type'] === 'preferred' ? 8 : 5,
                    'max_hours' => rand(6, 10),
                    'flexible' => rand(0, 1),
                    'constraints' => json_encode(['max_commute' => rand(10, 50)]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('employee_availabilities')->insert($availabilities);
        $this->command->info('Created ' . count($availabilities) . ' employee availabilities');
    }
}
