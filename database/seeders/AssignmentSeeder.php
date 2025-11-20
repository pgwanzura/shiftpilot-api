<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssignmentSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('assignments')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $assignments = [];
        $now = Carbon::now();

        // Get required data for assignments
        $contracts = DB::table('employer_agency_contracts')->pluck('id')->toArray();
        $agencyEmployees = DB::table('agency_employees')->where('status', 'active')->get();
        $locations = DB::table('locations')->pluck('id')->toArray();
        $users = DB::table('users')->whereIn('role', ['super_admin', 'agency_admin', 'agent'])->pluck('id')->toArray();

        if (empty($contracts) || $agencyEmployees->isEmpty() || empty($locations) || empty($users)) {
            $this->command->warn('Not enough data to create assignments. Need contracts, agency employees, locations, and users.');
            return;
        }

        $positionRates = [
            'Registered Nurse' => [32.00, 38.00],
            'Senior Care Assistant' => [16.00, 20.00],
            'Healthcare Assistant' => [13.50, 16.50],
            'Head Chef' => [35.00, 42.00],
            'Sous Chef' => [24.00, 30.00],
            'Line Cook' => [14.50, 18.00],
            'HGV Driver' => [20.00, 25.00],
            'Delivery Driver' => [13.50, 16.50],
            'Warehouse Operative' => [12.50, 15.00],
            'Retail Supervisor' => [14.00, 17.00],
            'Security Officer' => [13.00, 16.00],
            'Commercial Cleaner' => [11.50, 14.00]
        ];

        foreach ($agencyEmployees as $agencyEmployee) {
            $contractId = $contracts[array_rand($contracts)];
            $locationId = $locations[array_rand($locations)];
            $createdById = $users[array_rand($users)];

            $position = $agencyEmployee->position;
            $baseRate = $agencyEmployee->pay_rate;
            $rateRange = $positionRates[$position] ?? [15.00, 20.00];

            // Calculate rates with markup
            $agreedRate = round(rand($rateRange[0] * 100, $rateRange[1] * 100) / 100, 2);
            $markupPercent = rand(15, 30); // 15-30% markup
            $markupAmount = round($agreedRate * ($markupPercent / 100), 2);
            $payRate = round($agreedRate - $markupAmount, 2);

            $startDate = $now->copy()->subMonths(rand(1, 6));
            $endDate = rand(0, 10) > 2 ? $startDate->copy()->addMonths(rand(3, 12)) : null;

            $assignments[] = [
                'contract_id' => $contractId,
                'agency_employee_id' => $agencyEmployee->id,
                'shift_request_id' => null, // Optional field
                'agency_response_id' => null, // Optional field
                'location_id' => $locationId,
                'role' => $position,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
                'expected_hours_per_week' => rand(20, 40),
                'agreed_rate' => $agreedRate,
                'pay_rate' => $payRate,
                'markup_amount' => $markupAmount,
                'markup_percent' => $markupPercent,
                'status' => $this->getAssignmentStatus($startDate, $endDate),
                'assignment_type' => ['direct', 'temp', 'contract'][array_rand([0, 1, 2])],
                'shift_pattern' => json_encode($this->generateShiftPattern()),
                'notes' => "Assignment for {$position} at location {$locationId}",
                'created_by_id' => $createdById,
                'created_at' => $startDate->copy()->subDays(rand(1, 14)),
                'updated_at' => $now,
            ];
        }

        DB::table('assignments')->insert($assignments);
        $this->command->info('Created ' . count($assignments) . ' assignments');

        // Debug: Show assignment distribution
        $this->debugAssignmentDistribution();
    }

    private function getAssignmentStatus(Carbon $startDate, ?Carbon $endDate): string
    {
        $now = Carbon::now();

        if ($startDate->gt($now)) {
            return 'scheduled';
        } elseif ($endDate && $endDate->lt($now)) {
            return 'completed';
        } else {
            return ['active', 'active', 'active', 'paused'][array_rand([0, 0, 0, 1])];
        }
    }

    private function generateShiftPattern(): array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $pattern = [];

        foreach ($days as $day) {
            if (rand(0, 10) > 2) { // 70% chance of having a shift on this day
                $pattern[$day] = [
                    'start' => $this->generateShiftTime(),
                    'end' => $this->generateShiftTime(),
                    'duration' => rand(4, 12)
                ];
            }
        }

        return $pattern;
    }

    private function generateShiftTime(): string
    {
        $hours = rand(6, 22);
        $minutes = [0, 15, 30, 45][array_rand([0, 1, 2, 3])];
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    private function debugAssignmentDistribution()
    {
        $counts = DB::table('assignments')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Assignment status distribution:');
        foreach ($counts as $count) {
            $this->command->info("  {$count->status}: {$count->count} assignments");
        }

        $typeCounts = DB::table('assignments')
            ->select('assignment_type', DB::raw('count(*) as count'))
            ->groupBy('assignment_type')
            ->get();

        $this->command->info('Assignment type distribution:');
        foreach ($typeCounts as $count) {
            $this->command->info("  {$count->assignment_type}: {$count->count} assignments");
        }

        $total = DB::table('assignments')->count();
        $this->command->info("Total assignments: {$total}");
    }
}
