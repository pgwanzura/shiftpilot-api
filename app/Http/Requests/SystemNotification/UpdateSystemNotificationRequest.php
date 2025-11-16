<?php

namespace App\Http\Requests\SystemNotification;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('notification'));
    }

    public function rules(): array
    {
        return [
            'is_read' => 'sometimes|boolean',
        ];
    }
}
