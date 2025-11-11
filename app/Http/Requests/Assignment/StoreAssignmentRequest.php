<?php

namespace App\Http\Requests\Assignment;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Assignment::class);
    }

    public function rules(): array
    {
        return [
            'contract_id' => 'required|exists:employer_agency_contracts,id',
            'agency_employee_id' => 'required|exists:agency_employees,id',
            'shift_request_id' => 'nullable|exists:shift_requests,id',
            'agency_response_id' => 'nullable|exists:agency_responses,id',
            'location_id' => 'required|exists:locations,id',
            'role' => 'required|string|max:255',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'expected_hours_per_week' => 'nullable|integer|min:1|max:168',
            'agreed_rate' => 'required|numeric|min:0',
            'pay_rate' => 'required|numeric|min:0',
            'status' => ['sometimes', Rule::in(AssignmentStatus::values())],
            'assignment_type' => ['required', Rule::in(AssignmentType::values())],
            'shift_pattern' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->agreed_rate < $this->pay_rate) {
                $validator->errors()->add(
                    'agreed_rate',
                    'Agreed rate must be greater than or equal to pay rate'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'contract_id.required' => 'A valid contract is required',
            'agency_employee_id.required' => 'An agency employee must be selected',
            'location_id.required' => 'A work location must be selected',
            'agreed_rate.gte' => 'Agreed rate must be greater than or equal to pay rate',
            'start_date.after_or_equal' => 'Start date cannot be in the past',
        ];
    }
}
