<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftApprovalSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('shift_approvals')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $approvals = [];
        $now = Carbon::now();

        $approvedShifts = DB::table('shifts')
            ->where('status', 'completed')
            ->limit(20)
            ->get();

        foreach ($approvedShifts as $shift) {
            $contactId = $this->getRandomContact();
            if (!$contactId) continue;

            $approvals[] = [
                'shift_id' => $shift->id,
                'contact_id' => $contactId,
                'status' => 'approved',
                'signed_at' => Carbon::parse($shift->start_time)->copy()->addHours(rand(48, 168)),
                'notes' => 'Shift completed satisfactorily',
                'created_at' => Carbon::parse($shift->start_time)->copy()->addHours(rand(24, 72)),
                'updated_at' => $now,
            ];
        }

        DB::table('shift_approvals')->insert($approvals);
        $this->command->info('Created ' . count($approvals) . ' shift approvals');
    }

    private function getRandomContact(): ?int
    {
        $contact = DB::table('contacts')->inRandomOrder()->first();
        return $contact ? $contact->id : null;
    }
}
