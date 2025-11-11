<?php

namespace App\Http\Requests\Shift;

use App\Enums\ShiftStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShiftFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', \App\Models\Shift::class);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(ShiftStatus::values())],
            'assignment_id' => 'nullable|exists:assignments,id',
            'location_id' => 'nullable|exists:locations,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'agency_id' => 'nullable|exists:agencies,id',
            'employer_id' => 'nullable|exists:employers,id',
            'employee_id' => 'nullable|exists:employees,id',
            'search' => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
