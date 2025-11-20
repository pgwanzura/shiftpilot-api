<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessageSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('messages')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $messages = [];
        $now = Carbon::now();

        $conversations = DB::table('conversations')->get();
        $users = DB::table('users')->whereIn('role', ['agency_admin', 'agent', 'employer_admin', 'employee'])->get();

        if ($conversations->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No conversations or users found. Please run ConversationSeeder and UserSeeder first.');
            return;
        }

        $messageTemplates = [
            "Hi there, I wanted to follow up on the shift assignment from last week.",
            "Could you please confirm the schedule for tomorrow?",
            "The timesheet has been submitted for your approval.",
            "We have a new shift available that might interest you.",
            "Please review the attached documents when you have a moment.",
            "I need to request time off for next Friday.",
            "The client has provided some feedback on the recent work.",
            "Are you available for an emergency shift this evening?",
            "The payroll has been processed for this period.",
            "Let me know if you need any additional information.",
            "Thanks for your help with the recent assignment!",
            "Could we schedule a quick call to discuss this further?",
            "The shift has been completed successfully.",
            "I have updated the availability calendar.",
            "Please find the report attached for your review."
        ];

        foreach ($conversations as $conversation) {
            $participants = DB::table('conversation_participants')
                ->where('conversation_id', $conversation->id)
                ->pluck('user_id')
                ->toArray();

            if (empty($participants)) {
                continue;
            }

            $messageCount = rand(3, 12);
            $lastMessageTime = $now->copy()->subDays(rand(1, 30));

            for ($i = 0; $i < $messageCount; $i++) {
                $userId = $participants[array_rand($participants)];
                $messageTime = $lastMessageTime->copy()->addMinutes(rand(5, 240));

                $messages[] = [
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'content' => $messageTemplates[array_rand($messageTemplates)],
                    'message_type' => 'text',
                    'is_read' => $i === $messageCount - 1 ? false : true,
                    'read_at' => $i === $messageCount - 1 ? null : $messageTime->copy()->addMinutes(rand(1, 10)),
                    'attachments' => rand(0, 10) > 8 ? json_encode([['name' => 'document.pdf', 'size' => 1024000]]) : null,
                    'deleted_at' => null,
                    'created_at' => $messageTime,
                    'updated_at' => $messageTime,
                ];

                $lastMessageTime = $messageTime;
            }
        }

        foreach (array_chunk($messages, 100) as $chunk) {
            DB::table('messages')->insert($chunk);
        }

        $this->command->info('Created ' . count($messages) . ' messages');
        $this->debugMessageDistribution();
    }

    private function debugMessageDistribution(): void
    {
        $conversationCounts = DB::table('messages')
            ->select('conversation_id', DB::raw('count(*) as message_count'))
            ->groupBy('conversation_id')
            ->get();

        $this->command->info('Messages per conversation:');
        foreach ($conversationCounts as $count) {
            $this->command->info("  Conversation {$count->conversation_id}: {$count->message_count} messages");
        }

        $typeCounts = DB::table('messages')
            ->select('message_type', DB::raw('count(*) as count'))
            ->groupBy('message_type')
            ->get();

        $this->command->info('Message type distribution:');
        foreach ($typeCounts as $count) {
            $this->command->info("  {$count->message_type}: {$count->count}");
        }

        $readStats = DB::table('messages')
            ->selectRaw('COUNT(*) as total, SUM(is_read) as read_count')
            ->first();

        $readPercentage = $readStats->total > 0 ? round(($readStats->read_count / $readStats->total) * 100, 1) : 0;
        $this->command->info("Read status: {$readStats->read_count}/{$readStats->total} ({$readPercentage}%)");

        $userMessageCounts = DB::table('messages')
            ->select('user_id', DB::raw('count(*) as message_count'))
            ->groupBy('user_id')
            ->orderByDesc('message_count')
            ->limit(5)
            ->get();

        $this->command->info('Top 5 message senders:');
        foreach ($userMessageCounts as $count) {
            $this->command->info("  User {$count->user_id}: {$count->message_count} messages");
        }
    }
}
