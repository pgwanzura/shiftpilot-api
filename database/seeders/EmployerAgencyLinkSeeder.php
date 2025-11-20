<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployerAgencyLinkSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('employer_agency_links')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $links = [];
        $now = Carbon::now();

        $relationships = [
            [1, 1],
            [1, 2],
            [2, 1],
            [2, 3],
            [3, 2],
            [3, 4],
            [4, 3],
            [4, 5],
            [5, 1],
            [5, 4],
            [6, 2],
            [6, 5],
            [7, 3],
            [7, 1],
            [8, 4],
            [8, 2],
            [1, 3],
            [2, 4]
        ];

        foreach ($relationships as $rel) {
            $startDate = $now->copy()->subMonths(rand(6, 18));
            $links[] = [
                'employer_id' => $rel[0],
                'agency_id' => $rel[1],
                'status' => 'approved',
                'contract_start' => $startDate->format('Y-m-d'),
                'contract_end' => $startDate->copy()->addYears(2)->format('Y-m-d'),
                'terms' => 'Master Services Agreement for temporary staffing provision',
                'created_at' => $startDate,
                'updated_at' => $now,
            ];
        }

        DB::table('employer_agency_links')->insert($links);
        $this->command->info('Created ' . count($links) . ' employer-agency relationships');
    }
}
