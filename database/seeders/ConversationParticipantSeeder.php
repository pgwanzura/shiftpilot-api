<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConversationParticipantSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('conversation_participants')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $participants = [];
        $now = Carbon::now();

        $conversations = DB::table('conversations')->get();
        $users = DB::table('users')->get();

        if ($conversations->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No conversations or users found. Please run ConversationSeeder and UserSeeder first.');
            return;
        }

        foreach ($conversations as $conversation) {
            $participantCount = rand(2, 6);
            $availableUsers = $users->shuffle()->take($participantCount);

            foreach ($availableUsers as $user) {
                $joinedAt = $now->copy()->subDays(rand(1, 90));
                $shouldHaveLeft = rand(0, 10) > 7;
                $shouldBeMuted = rand(0, 10) > 8;

                $participants[] = [
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                    'role' => 'participant',
                    'joined_at' => $joinedAt,
                    'left_at' => $shouldHaveLeft ? $joinedAt->copy()->addDays(rand(1, 30)) : null,
                    'muted_until' => $shouldBeMuted ? $now->copy()->addDays(rand(1, 7)) : null,
                    'created_at' => $joinedAt,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($participants, 100) as $chunk) {
            DB::table('conversation_participants')->insert($chunk);
        }

        $this->command->info('Created ' . count($participants) . ' conversation participants');
        $this->debugParticipantDistribution();
    }

    private function debugParticipantDistribution(): void
    {
        $conversationStats = DB::table('conversation_participants')
            ->select('conversation_id', DB::raw('count(*) as participant_count'))
            ->groupBy('conversation_id')
            ->get();

        $this->command->info('Participants per conversation:');
        foreach ($conversationStats as $stat) {
            $this->command->info("  Conversation {$stat->conversation_id}: {$stat->participant_count} participants");
        }

        $activeParticipants = DB::table('conversation_participants')
            ->whereNull('left_at')
            ->count();

        $totalParticipants = DB::table('conversation_participants')->count();
        $activePercentage = $totalParticipants > 0 ? round(($activeParticipants / $totalParticipants) * 100, 1) : 0;

        $this->command->info("Active participants: {$activeParticipants}/{$totalParticipants} ({$activePercentage}%)");

        $mutedParticipants = DB::table('conversation_participants')
            ->whereNotNull('muted_until')
            ->count();

        $this->command->info("Muted participants: {$mutedParticipants}");

        $uniqueUsers = DB::table('conversation_participants')
            ->distinct('user_id')
            ->count('user_id');

        $totalUsers = DB::table('users')->count();
        $coveragePercentage = $totalUsers > 0 ? round(($uniqueUsers / $totalUsers) * 100, 1) : 0;

        $this->command->info("User coverage: {$uniqueUsers}/{$totalUsers} users in conversations ({$coveragePercentage}%)");
    }
}
