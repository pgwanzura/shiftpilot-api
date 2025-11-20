<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MessageRecipientSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('message_recipients')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $recipients = [];
        $now = Carbon::now();

        // Get all messages and their conversations
        $messages = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->select('messages.id as message_id', 'messages.conversation_id', 'messages.user_id')
            ->get();

        foreach ($messages as $message) {
            // Get all participants in this conversation (except the sender)
            $participants = DB::table('conversation_participants')
                ->where('conversation_id', $message->conversation_id)
                ->where('user_id', '!=', $message->user_id)
                ->whereNull('left_at')
                ->get();

            foreach ($participants as $participant) {
                $isRead = rand(0, 10) > 3;

                $recipients[] = [
                    'message_id' => $message->message_id,
                    'user_id' => $participant->user_id,
                    'is_read' => $isRead,
                    'read_at' => $isRead ? $now->copy()->subHours(rand(1, 24)) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('message_recipients')->insert($recipients);
        $this->command->info('Created ' . count($recipients) . ' message recipients');
    }
}
