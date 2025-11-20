<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConversationSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('conversations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $conversations = [];
        $now = Carbon::now();

        // Direct conversations
        for ($i = 1; $i <= 20; $i++) {
            $conversations[] = [
                'title' => null,
                'conversation_type' => 'direct',
                'context_type' => null,
                'context_id' => null,
                'last_message_id' => null,
                'last_message_at' => $now->copy()->subHours(rand(1, 168)),
                'archived_at' => rand(0, 10) > 8 ? $now->copy()->subDays(rand(1, 30)) : null,
                'created_at' => $now->copy()->subDays(rand(1, 90)),
                'updated_at' => $now,
            ];
        }

        // Group conversations
        for ($i = 1; $i <= 10; $i++) {
            $conversations[] = [
                'title' => 'Team Chat ' . $i,
                'conversation_type' => 'group',
                'context_type' => 'agency',
                'context_id' => rand(1, 5),
                'last_message_id' => null,
                'last_message_at' => $now->copy()->subHours(rand(1, 72)),
                'archived_at' => null,
                'created_at' => $now->copy()->subDays(rand(1, 60)),
                'updated_at' => $now,
            ];
        }

        // Shift conversations
        for ($i = 1; $i <= 15; $i++) {
            $conversations[] = [
                'title' => 'Shift Discussion ' . $i,
                'conversation_type' => 'shift',
                'context_type' => 'shift',
                'context_id' => $i,
                'last_message_id' => null,
                'last_message_at' => $now->copy()->subHours(rand(1, 48)),
                'archived_at' => null,
                'created_at' => $now->copy()->subDays(rand(1, 30)),
                'updated_at' => $now,
            ];
        }

        DB::table('conversations')->insert($conversations);
        $this->command->info('Created ' . count($conversations) . ' conversations');
    }
}