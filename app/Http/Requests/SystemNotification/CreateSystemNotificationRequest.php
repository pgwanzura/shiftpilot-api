<?php

namespace App\Http\Requests\SystemNotification;

use Illuminate\Foundation\Http\FormRequest;

class CreateSystemNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\SystemNotification::class);
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'channel' => 'required|in:in_app,email,sms,push',
            'template_key' => 'required|string|max:255',
            'payload' => 'nullable|array',
        ];
    }
}
