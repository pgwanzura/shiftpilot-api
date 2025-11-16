<?php

namespace App\Http\Requests\PricePlan;

use Illuminate\Foundation\Http\FormRequest;

class CreatePricePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'plan_key' => 'required|string|unique:price_plans,plan_key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_amount' => 'required|numeric|min:0',
            'billing_interval' => 'required|in:monthly,yearly',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
