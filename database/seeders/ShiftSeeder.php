<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('shifts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $shifts = [];
        $now = Carbon::now();

        $assignments = DB::table('assignments')
            ->where('status', 'active')
            ->select('id', 'location_id', 'agency_employee_id')
            ->limit(100)
            ->get();

        if ($assignments->isEmpty()) {
            $this->command->warn('No active assignments found. Please run AssignmentSeeder first.');
            return;
        }

        foreach ($assignments as $assignment) {
            $numShifts = rand(1, 4);

            for ($i = 1; $i <= $numShifts; $i++) {
                $isPast = rand(0, 10) > 3;
                $baseDate = $isPast ?
                    $now->copy()->subDays(rand(1, 90)) :
                    $now->copy()->addDays(rand(1, 60));

                $startTime = $baseDate->copy()
                    ->setHour(rand(6, 10))
                    ->setMinute(0);
                $endTime = $startTime->copy()->addHours(rand(4, 12));

                $agencyEmployee = DB::table('agency_employees')
                    ->where('id', $assignment->agency_employee_id)
                    ->first();

                $shifts[] = [
                    'assignment_id' => $assignment->id,
                    'location_id' => $assignment->location_id,
                    'shift_date' => $baseDate->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'hourly_rate' => $agencyEmployee ? $agencyEmployee->pay_rate : rand(1000, 2500) / 100,
                    'status' => $this->getShiftStatus($startTime),
                    'notes' => $this->getShiftNotes(),
                    'meta' => json_encode(['created_via' => 'seeder']),
                    'created_at' => $startTime->copy()->subDays(rand(1, 14)),
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($shifts, 100) as $chunk) {
            DB::table('shifts')->insert($chunk);
        }

        $this->command->info('Created ' . count($shifts) . ' shifts');
        $this->debugShiftDistribution();
    }

    private function getShiftStatus(Carbon $startTime): string
    {
        $now = Carbon::now();
        if ($startTime->gt($now)) {
            return 'scheduled';
        } else {
            return ['completed', 'completed', 'completed', 'no_show'][array_rand([0, 0, 0, 1])];
        }
    }

    private function getShiftNotes(): string
    {
        $notes = [
            'Standard shift assignment',
            'Cover for sick leave',
            'Additional support required',
            'Project-based work',
            'Seasonal demand',
            'Special event coverage'
        ];
        return $notes[array_rand($notes)];
    }

    private function debugShiftDistribution()
    {
        $statusCounts = DB::table('shifts')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Shift status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        $dateRange = DB::table('shifts')
            ->selectRaw('MIN(shift_date) as earliest, MAX(shift_date) as latest')
            ->first();

        $this->command->info("Shift date range: {$dateRange->earliest} to {$dateRange->latest}");

        $total = DB::table('shifts')->count();
        $this->command->info("Total shifts created: {$total}");
    }
}
