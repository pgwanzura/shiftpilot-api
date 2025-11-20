<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeOffRequestSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('time_off_requests')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $requests = [];
        $now = Carbon::now();

        for ($i = 1; $i <= 50; $i++) {
            $employeeId = rand(1, 100);
            $agencyId = DB::table('agency_employees')
                ->where('employee_id', $employeeId)
                ->inRandomOrder()
                ->value('agency_id');

            $type = ['vacation', 'sick', 'personal', 'bereavement'][array_rand([0, 1, 2, 3])];
            $startDate = $now->copy()->addDays(rand(10, 180));
            $duration = $type === 'sick' ? rand(1, 5) : rand(3, 14);
            $endDate = $startDate->copy()->addDays($duration);

            $requests[] = [
                'employee_id' => $employeeId,
                'agency_id' => $agencyId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'type' => $type,
                'reason' => $this->getTimeOffReason($type),
                'status' => $this->getTimeOffStatus($type),
                'approved_by_id' => $type !== 'sick' && rand(0, 10) > 3 ? rand(2, 6) : null,
                'approved_at' => $type !== 'sick' && rand(0, 10) > 3 ? $now->copy()->subDays(rand(1, 10)) : null,
                'created_at' => $now->copy()->subDays(rand(5, 30)),
                'updated_at' => $now,
            ];
        }

        DB::table('time_off_requests')->insert($requests);
        $this->command->info('Created ' . count($requests) . ' time off requests');
    }

    private function getTimeOffStatus(string $type): string
    {
        if ($type === 'sick') return 'approved';
        return ['pending', 'approved', 'approved', 'rejected'][array_rand([0, 1, 1, 2])];
    }

    private function getTimeOffReason(string $type): string
    {
        $reasons = [
            'vacation' => ['Family holiday', 'Travel', 'Personal time'],
            'sick' => ['Illness', 'Medical appointment', 'Recovery'],
            'personal' => ['Family emergency', 'Personal matters', 'Appointments'],
            'bereavement' => ['Bereavement leave', 'Family funeral']
        ];
        $typeReasons = $reasons[$type] ?? ['Time off request'];
        return $typeReasons[array_rand($typeReasons)];
    }
}
