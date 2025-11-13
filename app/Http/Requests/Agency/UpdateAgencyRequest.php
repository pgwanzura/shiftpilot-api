<?php

namespace App\Http\Requests\Agency;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('agency'));
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:100',
            'billing_email' => 'sometimes|required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'country' => 'nullable|string|size:2',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'default_markup_percent' => 'sometimes|required|numeric|min:0|max:100',
            'subscription_status' => ['sometimes', 'required', Rule::in(SubscriptionStatus::values())],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_status.in' => 'Invalid subscription status',
            'default_markup_percent.min' => 'Markup percent cannot be negative',
            'default_markup_percent.max' => 'Markup percent cannot exceed 100%',
        ];
    }
}