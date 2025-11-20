<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeePreferenceSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('employee_preferences')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $preferences = [];
        $now = Carbon::now();

        $shiftTypes = ['morning', 'afternoon', 'evening', 'night'];
        $locations = ['Central London', 'East London', 'West London', 'Manchester', 'Birmingham'];
        $industries = ['Healthcare', 'Retail', 'Hospitality', 'Logistics', 'Construction'];
        $roles = ['Nurse', 'Care Assistant', 'Driver', 'Warehouse Operative', 'Security Officer'];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $employmentTypes = ['temp', 'perm', 'part_time', 'zero_hours'];

        for ($employeeId = 1; $employeeId <= 100; $employeeId++) {
            $preferences[] = [
                'employee_id' => $employeeId,
                'preferred_shift_types' => json_encode(array_rand(array_flip($shiftTypes), rand(1, 3))),
                'preferred_locations' => json_encode(array_rand(array_flip($locations), rand(1, 3))),
                'preferred_industries' => json_encode(array_rand(array_flip($industries), rand(1, 2))),
                'preferred_roles' => json_encode(array_rand(array_flip($roles), rand(1, 2))),
                'max_travel_distance_km' => rand(10, 50),
                'min_hourly_rate' => round(rand(1000, 2000) / 100, 2),
                'preferred_shift_lengths' => json_encode([8, 10, 12]),
                'preferred_days' => json_encode(array_rand(array_flip($days), rand(3, 6))),
                'preferred_start_times' => json_encode(['08:00', '09:00', '10:00']),
                'preferred_employment_types' => json_encode(array_rand(array_flip($employmentTypes), rand(1, 2))),
                'notification_preferences' => json_encode([
                    'email' => rand(0, 1),
                    'sms' => rand(0, 1),
                    'push' => true
                ]),
                'communication_preferences' => json_encode([
                    'shift_offers' => true,
                    'shift_updates' => true,
                    'newsletters' => rand(0, 1)
                ]),
                'auto_accept_offers' => rand(0, 10) > 7,
                'max_shifts_per_week' => rand(3, 5),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('employee_preferences')->insert($preferences);
        $this->command->info('Created ' . count($preferences) . ' employee preferences');
    }
}