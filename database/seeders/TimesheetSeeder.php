<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TimesheetSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks before truncating
        Schema::disableForeignKeyConstraints();
        DB::table('timesheets')->truncate();
        Schema::enableForeignKeyConstraints();

        $timesheets = [];
        $now = Carbon::now();

        // Get completed shifts with their assignments and agency employees to get employee_id
        $shifts = DB::table('shifts')
            ->join('assignments', 'shifts.assignment_id', '=', 'assignments.id')
            ->join('agency_employees', 'assignments.agency_employee_id', '=', 'agency_employees.id')
            ->where('shifts.status', 'completed')
            ->select(
                'shifts.*',
                'agency_employees.employee_id',
                'assignments.id as assignment_id'
            )
            ->get();

        $users = DB::table('users')->whereIn('role', ['agency_admin', 'agent'])->get();
        $contacts = DB::table('contacts')->get();

        if ($shifts->isEmpty()) {
            $this->command->warn('No completed shifts found. Skipping timesheets.');
            return;
        }

        foreach ($shifts as $shift) {
            $status = ['pending', 'agency_approved', 'employer_approved', 'disputed', 'rejected'][rand(0, 4)];

            // Calculate clock times based on shift times
            $shiftStart = Carbon::parse($shift->start_time);
            $shiftEnd = Carbon::parse($shift->end_time);

            // Add some randomness to actual clock times
            $clockIn = $shiftStart->copy()->addMinutes(rand(-15, 30));
            $clockOut = $shiftEnd->copy()->addMinutes(rand(-30, 45));

            // Ensure clock_out is after clock_in
            if ($clockOut <= $clockIn) {
                $clockOut = $clockIn->copy()->addHours(rand(4, 10));
            }

            // Calculate hours worked
            $hoursWorked = $clockOut->diffInMinutes($clockIn) / 60;
            $breakMinutes = rand(0, 60);
            $hoursWorked -= ($breakMinutes / 60);
            $hoursWorked = max(0, round($hoursWorked, 2)); // Ensure positive hours

            // Set approval data based on status
            $agencyApprovedById = null;
            $agencyApprovedAt = null;
            $employerApprovedById = null;
            $employerApprovedAt = null;

            if (in_array($status, ['agency_approved', 'employer_approved', 'disputed'])) {
                $agencyApprovedById = $users->isNotEmpty() ? $users->random()->id : null;
                $agencyApprovedAt = $clockOut->copy()->addHours(rand(1, 24));
            }

            if (in_array($status, ['employer_approved', 'disputed'])) {
                $employerApprovedById = $contacts->isNotEmpty() ? $contacts->random()->id : null;
                $employerApprovedAt = $agencyApprovedAt ? $agencyApprovedAt->copy()->addHours(rand(1, 72)) : $clockOut->copy()->addHours(rand(24, 96));
            }

            $notes = [
                'Completed shift as scheduled',
                'Shift completed successfully',
                'All tasks completed',
                'Regular shift duties performed',
                'Met all shift requirements',
                'Productive shift completed'
            ][rand(0, 5)];

            // Add dispute reason if status is disputed
            if ($status === 'disputed') {
                $notes .= '. Dispute: ' . [
                    'Hours discrepancy noted',
                    'Break time clarification needed',
                    'Clock times need verification',
                    'Additional documentation required'
                ][rand(0, 3)];
            }

            // Add rejection reason if status is rejected
            if ($status === 'rejected') {
                $notes = 'Rejected: ' . [
                    'Incomplete timesheet',
                    'Missing documentation',
                    'Hours not approved',
                    'Discrepancy in recorded times'
                ][rand(0, 3)];
            }

            $timesheets[] = [
                'shift_id' => $shift->id,
                'employee_id' => $shift->employee_id, // This is the required field
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_minutes' => $breakMinutes,
                'hours_worked' => $hoursWorked,
                'status' => $status,
                'agency_approved_by_id' => $agencyApprovedById,
                'agency_approved_at' => $agencyApprovedAt,
                'employer_approved_by_id' => $employerApprovedById,
                'employer_approved_at' => $employerApprovedAt,
                'notes' => $notes,
                'attachments' => json_encode([]),
                'created_at' => $clockOut,
                'updated_at' => $now,
            ];
        }

        DB::table('timesheets')->insert($timesheets);
        $this->command->info('Created ' . count($timesheets) . ' timesheets');

        // Show distribution
        $statusCounts = DB::table('timesheets')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Timesheet status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        // Show average hours worked
        $avgHours = DB::table('timesheets')
            ->select(DB::raw('AVG(hours_worked) as avg_hours'))
            ->first();

        $this->command->info("Average hours worked: " . round($avgHours->avg_hours, 2));
    }
}
