<?php

namespace App\Services;

use App\Events\Messaging\MessageSent;
use App\Events\Messaging\MessageRead;
use App\Events\Messaging\ConversationUpdated;
use App\Events\Messaging\ParticipantRemoved;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Events\Messaging\ConversationCreated;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessagingService
{
    public function createConversation(array $data, int $creatorId): Conversation
    {
        return DB::transaction(function () use ($data, $creatorId) {
            $conversation = Conversation::create([
                'title' => $data['title'] ?? null,
                'conversation_type' => $data['conversation_type'],
                'context_type' => $data['context_type'] ?? null,
                'context_id' => $data['context_id'] ?? null,
            ]);

            $participantIds = array_unique(array_merge($data['participant_ids'], [$creatorId]));

            foreach ($participantIds as $participantId) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $participantId,
                    'role' => $participantId === $creatorId ? 'admin' : 'participant',
                    'joined_at' => now(),
                ]);
            }

            $conversation->load('participants.user');

            ConversationCreated::dispatch($conversation);

            return $conversation;
        });
    }

    public function sendMessage(array $data, int $senderId): Message
    {
        return DB::transaction(function () use ($data, $senderId) {
            $message = Message::create([
                'conversation_id' => $data['conversation_id'],
                'sender_id' => $senderId,
                'content' => $data['content'],
                'message_type' => $data['message_type'],
                'attachments' => $data['attachments'] ?? null,
            ]);

            $participants = ConversationParticipant::where('conversation_id', $data['conversation_id'])
                ->active()
                ->get();

            foreach ($participants as $participant) {
                MessageRecipient::create([
                    'message_id' => $message->id,
                    'user_id' => $participant->user_id,
                    'is_read' => $participant->user_id === $senderId,
                    'read_at' => $participant->user_id === $senderId ? now() : null,
                ]);
            }

            Conversation::where('id', $data['conversation_id'])->update([
                'last_message_id' => $message->id,
                'last_message_at' => now(),
            ]);

            $message->load(['sender', 'recipients.user']);

            MessageSent::dispatch($message);
            ConversationUpdated::dispatch($message->conversation);

            return $message;
        });
    }

    public function getUserConversations(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Conversation::whereHas('participants', function ($q) use ($userId) {
            $q->where('user_id', $userId)->active();
        })->with(['lastMessage.sender', 'participants.user']);

        if (isset($filters['conversation_type'])) {
            $query->where('conversation_type', $filters['conversation_type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                    ->orWhereHas('participants.user', function ($q) use ($filters) {
                        $q->where('name', 'like', "%{$filters['search']}%");
                    });
            });
        }

        return $query->orderBy('last_message_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function getConversationMessages(int $conversationId, int $userId, array $filters = []): LengthAwarePaginator
    {
        $this->markConversationAsRead($conversationId, $userId);

        $query = Message::where('conversation_id', $conversationId)
            ->with(['sender', 'recipients']);

        if (isset($filters['message_type'])) {
            $query->where('message_type', $filters['message_type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    public function markConversationAsRead(int $conversationId, int $userId): void
    {
        $updated = MessageRecipient::whereHas('message', function ($q) use ($conversationId) {
            $q->where('conversation_id', $conversationId);
        })->where('user_id', $userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if ($updated) {
            $recipients = MessageRecipient::whereHas('message', function ($q) use ($conversationId) {
                $q->where('conversation_id', $conversationId);
            })->where('user_id', $userId)
                ->with('message')
                ->get();

            foreach ($recipients as $recipient) {
                MessageRead::dispatch($recipient);
            }
        }
    }

    public function addParticipant(int $conversationId, int $userId, int $adderId): ConversationParticipant
    {
        $participant = ConversationParticipant::create([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'role' => 'participant',
            'joined_at' => now(),
        ]);

        ConversationUpdated::dispatch(Conversation::find($conversationId));

        return $participant;
    }

    public function removeParticipant(int $conversationId, int $userId): bool
    {
        $participant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $result = $participant->leave();

        ParticipantRemoved::dispatch($participant);
        ConversationUpdated::dispatch(Conversation::find($conversationId));

        return $result;
    }

    public function getUnreadCount(int $userId): int
    {
        return MessageRecipient::where('user_id', $userId)
            ->unread()
            ->count();
    }
}
