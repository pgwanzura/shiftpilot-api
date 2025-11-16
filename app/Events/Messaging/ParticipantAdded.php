<?php

namespace App\Events\Messaging;

use App\Models\ConversationParticipant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ConversationParticipant $participant
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->participant->conversation_id}"),
            new PrivateChannel("user.{$this->participant->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'participant.added';
    }
}
