<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AgencyAssignmentResponseSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks before truncating
        Schema::disableForeignKeyConstraints();
        DB::table('agency_assignment_responses')->truncate();
        Schema::enableForeignKeyConstraints();

        $responses = [];
        $now = Carbon::now();

        $assignments = DB::table('assignments')->get();
        $agencies = DB::table('agencies')->get();

        if ($assignments->isEmpty() || $agencies->isEmpty()) {
            $this->command->warn('No assignments or agencies found. Skipping agency assignment responses.');
            return;
        }

        foreach ($assignments as $assignment) {
            $responseCount = rand(1, min(3, $agencies->count())); // Ensure we don't request more agencies than available
            $respondingAgencies = $agencies->random($responseCount);

            foreach ($respondingAgencies as $agency) {
                $status = ['submitted', 'reviewed', 'accepted', 'rejected'][rand(0, 3)];

                // Only add rejection reason if status is rejected
                $rejectionReason = null;
                if ($status === 'rejected') {
                    $rejectionReason = "We've decided to go with another agency for this assignment.";
                }

                $responses[] = [
                    'assignment_id' => $assignment->id,
                    'agency_id' => $agency->id,
                    'proposal_text' => "We can provide qualified staff for this assignment with our expertise in similar roles. Our team has extensive experience in this field and we're confident we can deliver excellent results.",
                    'proposed_rate' => rand(1500, 3000) / 100, // £15.00 - £30.00
                    'estimated_hours' => rand(20, 40),
                    'status' => $status,
                    'rejection_reason' => $rejectionReason,
                    'submitted_at' => $now->copy()->subDays(rand(1, 30)),
                    'responded_at' => in_array($status, ['reviewed', 'accepted', 'rejected']) ? $now->copy()->subDays(rand(1, 15)) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('agency_assignment_responses')->insert($responses);
        $this->command->info('Created ' . count($responses) . ' agency assignment responses');

        // Show distribution
        $statusCounts = DB::table('agency_assignment_responses')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Agency assignment response status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        // Show responses per assignment
        $assignmentCounts = DB::table('agency_assignment_responses')
            ->select('assignment_id', DB::raw('count(*) as response_count'))
            ->groupBy('assignment_id')
            ->get();

        $this->command->info('Responses per assignment:');
        foreach ($assignmentCounts as $count) {
            $this->command->info("  Assignment {$count->assignment_id}: {$count->response_count} responses");
        }
    }
}
