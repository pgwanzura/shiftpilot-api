<?php

namespace App\Http\Requests\Messaging\Message;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Conversation;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('sendMessage', Conversation::find($this->conversation_id));
    }

    public function rules(): array
    {
        return [
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string|max:5000',
            'message_type' => 'required|in:text,image,file,system',
            'attachments' => 'sometimes|array',
            'attachments.*.name' => 'required|string',
            'attachments.*.url' => 'required|url',
            'attachments.*.size' => 'sometimes|integer',
        ];
    }
}
