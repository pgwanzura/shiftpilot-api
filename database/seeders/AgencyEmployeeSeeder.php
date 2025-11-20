<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgencyEmployeeSeeder extends Seeder
{
    private array $positions = [
        'Registered Nurse',
        'Senior Care Assistant',
        'Healthcare Assistant',
        'Head Chef',
        'Sous Chef',
        'Line Cook',
        'Kitchen Porter',
        'HGV Driver',
        'Delivery Driver',
        'Van Driver',
        'Forklift Operator',
        'Warehouse Operative',
        'Stock Controller',
        'Order Picker',
        'Retail Supervisor',
        'Sales Assistant',
        'Customer Service Advisor',
        'Commercial Cleaner',
        'Office Cleaner',
        'Industrial Cleaner',
        'Security Officer',
        'Security Supervisor',
        'Event Security',
        'Administrative Assistant',
        'Receptionist',
        'Data Entry Clerk'
    ];

    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('agency_employees')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $agencyEmployees = [];
        $now = Carbon::now();

        $positionRates = [
            'Registered Nurse' => [25.50, 32.00],
            'Senior Care Assistant' => [12.50, 16.00],
            'Healthcare Assistant' => [10.50, 13.50],
            'Head Chef' => [28.00, 35.00],
            'Sous Chef' => [18.00, 24.00],
            'Line Cook' => [11.00, 14.50],
            'HGV Driver' => [15.00, 20.00],
            'Delivery Driver' => [10.50, 13.50],
            'Warehouse Operative' => [9.50, 12.50],
            'Retail Supervisor' => [11.00, 14.00],
            'Security Officer' => [10.00, 13.00],
            'Commercial Cleaner' => [9.00, 11.50]
        ];

        // Get actual agency IDs from the database
        $agencyIds = DB::table('agencies')->pluck('id')->toArray();

        if (empty($agencyIds)) {
            $this->command->error('No agencies found! AgencySeeder must run before AgencyEmployeeSeeder.');
            return;
        }

        $this->command->info("Found agencies with IDs: " . implode(', ', $agencyIds));

        // Get available branch IDs - ensure we have branches
        $branchIds = DB::table('agency_branches')->pluck('id')->toArray();

        if (empty($branchIds)) {
            $this->command->error('No agency branches found! AgencyBranchSeeder must run before AgencyEmployeeSeeder.');
            return;
        }

        $this->command->info("Found " . count($branchIds) . " agency branches to assign employees to.");

        for ($employeeId = 1; $employeeId <= 100; $employeeId++) {
            $numAgencies = rand(1, 3);

            // Select random agency IDs from the actual available agencies
            $selectedAgencyIds = [];
            $availableAgencies = $agencyIds;

            for ($i = 0; $i < $numAgencies; $i++) {
                if (empty($availableAgencies)) break;

                $randomIndex = array_rand($availableAgencies);
                $selectedAgencyIds[] = $availableAgencies[$randomIndex];

                // Remove the selected agency to avoid duplicates for this employee
                unset($availableAgencies[$randomIndex]);
                $availableAgencies = array_values($availableAgencies); // Reindex
            }

            foreach ($selectedAgencyIds as $agencyId) {
                $position = $this->positions[array_rand($this->positions)];
                $rateRange = $positionRates[$position] ?? [10.00, 15.00];
                $payRate = round(rand($rateRange[0] * 100, $rateRange[1] * 100) / 100, 2);

                // Get a random branch for this agency - ensure we always get a valid branch
                $agencyBranches = DB::table('agency_branches')
                    ->where('agency_id', $agencyId)
                    ->pluck('id')
                    ->toArray();

                if (empty($agencyBranches)) {
                    // If no branches for this agency, use any random branch
                    $branchId = $branchIds[array_rand($branchIds)];
                } else {
                    $branchId = $agencyBranches[array_rand($agencyBranches)];
                }

                $agencyEmployees[] = [
                    'agency_id' => $agencyId,
                    'employee_id' => $employeeId,
                    'agency_branch_id' => $branchId, // Always a valid branch ID
                    'position' => $position,
                    'pay_rate' => $payRate,
                    'employment_type' => $this->getEmploymentType($position),
                    'status' => 'active',
                    'contract_start_date' => $now->copy()->subMonths(rand(1, 12))->format('Y-m-d'),
                    'specializations' => json_encode($this->getRelevantSpecializations($position)),
                    'max_weekly_hours' => rand(20, 48),
                    'meta' => json_encode([
                        'preferred_locations' => $this->getPreferredLocations(),
                        'skills' => $this->getRelevantSkills($position)
                    ]),
                    'created_at' => $now->copy()->subMonths(rand(1, 12)),
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('agency_employees')->insert($agencyEmployees);
        $this->command->info('Created ' . count($agencyEmployees) . ' agency-employee relationships');

        // Debug: Show employment type and branch distribution
        $this->debugDistribution();
    }

    private function getEmploymentType(string $position): string
    {
        // Only use 'temp' or 'contract' as allowed by the ENUM
        $employmentTypes = ['temp', 'contract'];

        // Weight the distribution - more temp workers than contract
        // 70% temp, 30% contract
        $random = rand(1, 10);

        if ($random <= 7) {
            return 'temp';
        } else {
            return 'contract';
        }
    }

    private function getRelevantSpecializations(string $position): array
    {
        if (str_contains($position, 'Nurse')) return ['Acute Care', 'Medical Ward', 'Surgical'];
        if (str_contains($position, 'Care')) return ['Elderly Care', 'Dementia', 'Personal Care'];
        if (str_contains($position, 'Chef')) return ['Fine Dining', 'Banquets', 'A La Carte'];
        if (str_contains($position, 'Driver')) return ['Long Haul', 'Local Delivery', 'Refrigerated'];
        if (str_contains($position, 'Warehouse')) return ['Picking', 'Packing', 'Inventory'];
        return ['General'];
    }

    private function getPreferredLocations(): array
    {
        $locations = ['Central London', 'East London', 'West London', 'Manchester City Centre', 'Birmingham'];
        $selected = array_rand($locations, rand(1, 3));
        return is_array($selected) ? array_intersect_key($locations, array_flip($selected)) : [$locations[$selected]];
    }

    private function getRelevantSkills(string $position): array
    {
        $skills = [];
        if (str_contains($position, 'Nurse') || str_contains($position, 'Care')) {
            $skills = ['Patient Care', 'Medication Administration', 'Wound Care'];
        } elseif (str_contains($position, 'Chef') || str_contains($position, 'Cook')) {
            $skills = ['Food Preparation', 'Menu Planning', 'Kitchen Management'];
        } elseif (str_contains($position, 'Driver')) {
            $skills = ['Route Planning', 'Vehicle Maintenance', 'Customer Service'];
        } else {
            $skills = ['Teamwork', 'Communication', 'Problem Solving'];
        }
        return array_slice($skills, 0, rand(2, 4));
    }

    private function debugDistribution()
    {
        // Employment type distribution
        $employmentCounts = DB::table('agency_employees')
            ->select('employment_type', DB::raw('count(*) as count'))
            ->groupBy('employment_type')
            ->get();

        $this->command->info('Employment type distribution:');
        foreach ($employmentCounts as $count) {
            $this->command->info("  {$count->employment_type}: {$count->count} employees");
        }

        // Agency distribution
        $agencyCounts = DB::table('agency_employees')
            ->join('agencies', 'agency_employees.agency_id', '=', 'agencies.id')
            ->select('agencies.name', DB::raw('count(*) as count'))
            ->groupBy('agencies.id', 'agencies.name')
            ->get();

        $this->command->info('Agency distribution:');
        foreach ($agencyCounts as $count) {
            $this->command->info("  {$count->name}: {$count->count} employees");
        }

        $total = DB::table('agency_employees')->count();
        $this->command->info("Total agency employee relationships: {$total}");
    }
}
