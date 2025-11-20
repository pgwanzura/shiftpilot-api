<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgentSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('agents')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $agents = [];
        $now = Carbon::now();

        // First, let's check if agency_branches exist, if not, we'll use null or create some
        $hasBranches = DB::table('agency_branches')->exists();

        if (!$hasBranches) {
            $this->command->info('No agency branches found. Creating some branches first...');
            $this->createAgencyBranches();
        }

        // Get available branch IDs
        $branchIds = DB::table('agency_branches')->pluck('id')->toArray();

        for ($i = 1; $i <= 15; $i++) {
            $agencyId = ceil($i / 3);
            $branchId = !empty($branchIds) ? $branchIds[array_rand($branchIds)] : null;

            $agents[] = [
                'user_id' => 7 + $i, // After super admin (1) + 5 agency admins (2-6)
                'agency_id' => $agencyId,
                'agency_branch_id' => $branchId, // Add the required field
                'permissions' => json_encode(['shift_management', 'employee_management', 'timesheet_approval']),
                'created_at' => $now->copy()->subMonths(rand(3, 12)),
                'updated_at' => $now,
            ];
        }

        DB::table('agents')->insert($agents);
        $this->command->info('Created ' . count($agents) . ' agents');
    }

    private function createAgencyBranches()
    {
        $branches = [];
        $now = Carbon::now();

        $branchData = [
            [1, 'London Head Office'],
            [1, 'Manchester Branch'],
            [2, 'Birmingham Main'],
            [2, 'Leeds Office'],
            [3, 'Glasgow Central'],
            [4, 'London West'],
            [5, 'Bristol Branch']
        ];

        foreach ($branchData as $data) {
            $branches[] = [
                'agency_id' => $data[0],
                'name' => $data[1],
                'address_line1' => rand(1, 100) . ' Business Street',
                'city' => explode(' ', $data[1])[0], // Get city from branch name
                'postcode' => $this->generatePostcode(explode(' ', $data[1])[0]),
                'country' => 'GB',
                'phone' => '+441632960' . rand(100, 999),
                'email' => strtolower(str_replace(' ', '', $data[1])) . '@example.com',
                // 'manager_id' => null, // Will be set later if needed
                'status' => 'active',
                'created_at' => $now->copy()->subMonths(rand(6, 18)),
                'updated_at' => $now,
            ];
        }

        DB::table('agency_branches')->insert($branches);
        $this->command->info('Created ' . count($branches) . ' agency branches');
    }

    private function generatePostcode(string $city): string
    {
        $prefixes = [
            'London' => ['E1', 'W1', 'SW1', 'NW1', 'SE1'],
            'Manchester' => ['M1', 'M2', 'M3', 'M4'],
            'Birmingham' => ['B1', 'B2', 'B3', 'B4'],
            'Leeds' => ['LS1', 'LS2', 'LS3', 'LS4'],
            'Glasgow' => ['G1', 'G2', 'G3', 'G4'],
            'Bristol' => ['BS1', 'BS2', 'BS3', 'BS4']
        ];

        $prefix = $prefixes[$city][array_rand($prefixes[$city])] ?? 'AB1';
        return $prefix . ' ' . rand(1, 9) . 'AB';
    }
}
