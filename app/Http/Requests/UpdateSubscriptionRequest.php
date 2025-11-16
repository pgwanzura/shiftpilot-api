<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('subscription'));
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|in:active,past_due,cancelled,suspended',
            'current_period_start' => 'sometimes|date',
            'current_period_end' => 'sometimes|date|after:current_period_start',
        ];
    }
}
