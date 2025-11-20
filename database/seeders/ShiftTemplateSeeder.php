<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftTemplateSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('shift_templates')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $templates = [];
        $now = Carbon::now();

        $assignments = DB::table('assignments')
            ->where('status', 'active')
            ->limit(50)
            ->get();

        if ($assignments->isEmpty()) {
            $this->command->warn('No active assignments found. Please run AssignmentSeeder first.');
            return;
        }

        $createdByUser = DB::table('users')
            ->whereIn('role', ['super_admin', 'agency_admin', 'agent'])
            ->inRandomOrder()
            ->first();

        if (!$createdByUser) {
            $this->command->error('No suitable user found for created_by_id. Cannot create shift templates.');
            return;
        }

        $templateData = [
            ['Early Nursing Shift', 'Provides morning healthcare coverage', '06:00', '14:00'],
            ['Late Nursing Shift', 'Evening healthcare services', '14:00', '22:00'],
            ['Night Nursing Shift', 'Overnight patient care', '22:00', '06:00'],
            ['Warehouse Morning', 'Morning inventory and shipping', '08:00', '16:00'],
            ['Warehouse Afternoon', 'Afternoon logistics operations', '16:00', '00:00'],
            ['Retail Day', 'Daytime customer service', '09:00', '17:00'],
            ['Retail Evening', 'Evening retail operations', '13:00', '21:00'],
            ['Security Day', 'Daytime security patrol', '08:00', '20:00'],
            ['Security Night', 'Overnight security monitoring', '20:00', '08:00']
        ];

        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($assignments as $assignment) {
            $templateCount = rand(1, 3);

            for ($i = 0; $i < $templateCount; $i++) {
                $template = $templateData[array_rand($templateData)];
                $dayOfWeek = $daysOfWeek[array_rand($daysOfWeek)];

                $templates[] = [
                    'assignment_id' => $assignment->id,
                    'title' => $template[0],
                    'description' => $template[1],
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $template[2],
                    'end_time' => $template[3],
                    'recurrence_type' => 'weekly',
                    'recurrence_rules' => json_encode([
                        'interval' => 1,
                        'by_day' => [$dayOfWeek],
                        'count' => rand(10, 52)
                    ]),
                    'timezone' => 'Europe/London',
                    'status' => 'active',
                    'effective_start_date' => $now->copy()->startOfWeek()->format('Y-m-d'),
                    'effective_end_date' => $now->copy()->addMonths(6)->format('Y-m-d'),
                    'max_occurrences' => rand(20, 100),
                    'auto_publish' => rand(0, 1),
                    'generation_count' => rand(0, 10),
                    'last_generated_date' => rand(0, 1) ? $now->copy()->subDays(rand(1, 7))->format('Y-m-d') : null,
                    'meta' => json_encode([
                        'shift_category' => $this->getShiftCategory($template[0]),
                        'skill_level' => ['basic', 'intermediate', 'advanced'][array_rand([0, 1, 2])],
                        'uniform_required' => rand(0, 1)
                    ]),
                    'created_by_id' => $createdByUser->id,
                    'created_at' => $now->copy()->subMonths(2),
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($templates, 100) as $chunk) {
            DB::table('shift_templates')->insert($chunk);
        }

        $this->command->info('Created ' . count($templates) . ' shift templates');
        $this->debugTemplateDistribution();
    }

    private function getShiftCategory($title)
    {
        $lowerTitle = strtolower($title);

        if (str_contains($lowerTitle, 'nurs') || str_contains($lowerTitle, 'care')) {
            return 'healthcare';
        } elseif (str_contains($lowerTitle, 'warehouse') || str_contains($lowerTitle, 'logistics')) {
            return 'logistics';
        } elseif (str_contains($lowerTitle, 'retail')) {
            return 'retail';
        } elseif (str_contains($lowerTitle, 'security')) {
            return 'security';
        } else {
            return 'general';
        }
    }

    private function debugTemplateDistribution()
    {
        $statusCounts = DB::table('shift_templates')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $this->command->info('Shift template status distribution:');
        foreach ($statusCounts as $count) {
            $this->command->info("  {$count->status}: {$count->count}");
        }

        $dayCounts = DB::table('shift_templates')
            ->select('day_of_week', DB::raw('count(*) as count'))
            ->groupBy('day_of_week')
            ->orderBy('day_of_week')
            ->get();

        $this->command->info('Day of week distribution:');
        foreach ($dayCounts as $count) {
            $this->command->info("  {$count->day_of_week}: {$count->count}");
        }

        $assignmentCounts = DB::table('shift_templates')
            ->select(DB::raw('count(*) as template_count'))
            ->groupBy('assignment_id')
            ->get();

        $this->command->info('Templates per assignment: ' . $assignmentCounts->count() . ' assignments have templates');
    }
}
