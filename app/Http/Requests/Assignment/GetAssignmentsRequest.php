<?php

namespace App\Http\Requests\Assignment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetAssignmentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => ['nullable', 'string', Rule::in(['active', 'pending', 'completed', 'cancelled', 'suspended'])],
            'assignment_type' => ['nullable', 'string', Rule::in(['ongoing', 'fixed_term'])],
            'agency_id' => 'nullable|exists:agencies,id',
            'employer_id' => 'nullable|exists:employers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:255',
            'location_id' => 'nullable|exists:locations,id',
            'per_page' => 'nullable|integer|min:1',
        ];
    }
}
