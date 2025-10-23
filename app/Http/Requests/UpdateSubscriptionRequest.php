<?php

namespace App\Http\Requests\Subscription;

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
            'plan_key' => 'sometimes|string',
            'plan_name' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'interval' => 'sometimes|in:monthly,yearly',
            'status' => 'sometimes|in:active,past_due,cancelled',
            'current_period_start' => 'nullable|date',
            'current_period_end' => 'nullable|date|after:current_period_start',
        ];
    }
}
