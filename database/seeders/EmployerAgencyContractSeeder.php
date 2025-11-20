<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class EmployerAgencyContractSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks before truncating
        Schema::disableForeignKeyConstraints();
        DB::table('employer_agency_contracts')->truncate();
        Schema::enableForeignKeyConstraints();

        $contracts = [];
        $now = Carbon::now();

        $employers = DB::table('employers')->get();
        $agencies = DB::table('agencies')->get();

        if ($employers->isEmpty() || $agencies->isEmpty()) {
            $this->command->error('No employers or agencies found. Please run EmployerSeeder and AgencySeeder first.');
            return;
        }

        // Create contracts between employers and agencies
        foreach ($employers as $employer) {
            $agencyCount = rand(2, min(4, $agencies->count())); // Ensure we don't request more agencies than available
            $selectedAgencies = $agencies->random($agencyCount);

            foreach ($selectedAgencies as $agency) {
                $contracts[] = [
                    'employer_id' => $employer->id,
                    'agency_id' => $agency->id,
                    'status' => 'active',
                    'contract_document_url' => "https://docs.shiftpilot.com/contracts/{$employer->id}-{$agency->id}.pdf",
                    'contract_start' => $now->copy()->subMonths(rand(3, 18)),
                    'contract_end' => $now->copy()->addMonths(rand(6, 24)),
                    'terms' => "Standard staffing agreement between {$employer->name} and {$agency->name}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('employer_agency_contracts')->insert($contracts);
        $this->command->info('Created ' . count($contracts) . ' employer-agency contracts');

        // Show distribution
        $statusCounts = DB::table('employer_agency_contracts')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Contract status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }
    }
}
