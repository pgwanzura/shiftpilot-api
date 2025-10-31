<?php

namespace App\Http\Requests\Placement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PlacementStatus;

class PlacementFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => [
                'nullable',
                Rule::enum(PlacementStatus::class)
            ],
            'experience_level' => 'nullable|string|in:entry,intermediate,senior',
            'budget_type' => 'nullable|string|in:hourly,daily,fixed',
            'location_id' => 'nullable|exists:locations,id',
            'start_date_from' => 'nullable|date',
            'start_date_to' => 'nullable|date|after_or_equal:start_date_from',
            'sort_by' => 'nullable|string|in:title,start_date,budget_amount,created_at,status',
            'sort_direction' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date_to.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }
}
