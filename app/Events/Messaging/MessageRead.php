<?php

namespace App\Events\Messaging;

use App\Models\MessageRecipient;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MessageRecipient $messageRecipient
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("conversation.{$this->messageRecipient->message->conversation_id}"),
            new PrivateChannel("user.{$this->messageRecipient->message->sender_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }
}
