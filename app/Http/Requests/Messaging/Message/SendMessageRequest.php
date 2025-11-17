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
            'attachments' => 'sometimes|array|max:10',
            'attachments.*.name' => 'required|string|max:255',
            'attachments.*.url' => 'required|url|max:1000',
            'attachments.*.size' => 'required|integer|min:1|max:10485760', // 10MB max
            'attachments.*.mime_type' => 'required|string|max:100',
        ];
    }
}
