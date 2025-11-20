<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ShiftOfferSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks before truncating
        Schema::disableForeignKeyConstraints();
        DB::table('shift_offers')->truncate();
        Schema::enableForeignKeyConstraints();

        $offers = [];
        $now = Carbon::now();

        // Get shifts, agency_employees, agencies, and agents
        $shifts = DB::table('shifts')->get();
        $agencyEmployees = DB::table('agency_employees')->get();
        $agencies = DB::table('agencies')->get();
        $agents = DB::table('agents')->get();

        if ($shifts->isEmpty() || $agencyEmployees->isEmpty() || $agencies->isEmpty() || $agents->isEmpty()) {
            $this->command->warn('No shifts, agency employees, agencies, or agents found. Skipping shift offers.');
            return;
        }

        foreach ($shifts as $shift) {
            $offerCount = rand(1, min(3, $agencyEmployees->count()));
            $selectedAgencyEmployees = $agencyEmployees->random($offerCount);

            foreach ($selectedAgencyEmployees as $agencyEmployee) {
                // Get the agency for this agency_employee
                $agency = $agencies->firstWhere('id', $agencyEmployee->agency_id);
                if (!$agency) {
                    continue;
                }

                // Get agents from the same agency
                $agencyAgents = $agents->where('agency_id', $agency->id);
                if ($agencyAgents->isEmpty()) {
                    continue;
                }

                $agent = $agencyAgents->random();
                $status = ['pending', 'accepted', 'rejected', 'expired'][rand(0, 3)];

                // Set response notes based on status
                $responseNotes = null;
                if ($status === 'accepted') {
                    $responseNotes = ['Accepted shift', 'Available for this shift', 'Confirmed availability'][rand(0, 2)];
                } elseif ($status === 'rejected') {
                    $responseNotes = ['Unavailable due to other commitment', 'Location too far', 'Rate not acceptable', 'Accepted alternative shift'][rand(0, 3)];
                } elseif ($status === 'expired') {
                    $responseNotes = ['Offer expired', 'No response received'][rand(0, 1)];
                }

                // Set timestamps based on status
                $submittedAt = $now->copy()->subDays(rand(1, 3))->subHours(rand(1, 12));
                $expiresAt = $submittedAt->copy()->addHours(rand(24, 72));

                $respondedAt = null;
                if (in_array($status, ['accepted', 'rejected'])) {
                    $respondedAt = $submittedAt->copy()->addHours(rand(1, 48));
                } elseif ($status === 'expired') {
                    $respondedAt = $expiresAt->copy()->addHours(rand(1, 12));
                }

                $offers[] = [
                    'shift_id' => $shift->id,
                    'agency_employee_id' => $agencyEmployee->id,
                    'agency_id' => $agency->id,
                    'agent_id' => $agent->id,
                    'status' => $status,
                    'expires_at' => $expiresAt,
                    'responded_at' => $respondedAt,
                    'response_notes' => $responseNotes,
                    'created_at' => $submittedAt,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('shift_offers')->insert($offers);
        $this->command->info('Created ' . count($offers) . ' shift offers');

        // Show distribution
        $statusCounts = DB::table('shift_offers')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Shift offer status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        // Show offers per agency
        $agencyCounts = DB::table('shift_offers')
            ->join('agencies', 'shift_offers.agency_id', '=', 'agencies.id')
            ->select('agencies.name', DB::raw('count(*) as offer_count'))
            ->groupBy('agencies.id', 'agencies.name')
            ->get();

        $this->command->info('Offers per agency:');
        foreach ($agencyCounts as $count) {
            $this->command->info("  {$count->name}: {$count->offer_count} offers");
        }
    }
}
