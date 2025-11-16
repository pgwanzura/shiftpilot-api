<?php

namespace App\Http\Requests\PricePlan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePricePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'plan_key' => [
                'sometimes',
                'string',
                Rule::unique('price_plans', 'plan_key')->ignore($this->route('price_plan'))
            ],
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_amount' => 'sometimes|numeric|min:0',
            'billing_interval' => 'sometimes|in:monthly,yearly',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer',
        ];
    }
}
