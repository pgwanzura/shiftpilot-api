<?php

namespace App\Http\Requests\Assignment;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('assignment'));
    }

    public function rules(): array
    {
        return [
            'contract_id' => 'sometimes|exists:employer_agency_contracts,id',
            'agency_employee_id' => 'sometimes|exists:agency_employees,id',
            'shift_request_id' => 'nullable|exists:shift_requests,id',
            'agency_response_id' => 'nullable|exists:agency_responses,id',
            'location_id' => 'sometimes|exists:locations,id',
            'role' => 'sometimes|string|max:255',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'expected_hours_per_week' => 'nullable|integer|min:1|max:168',
            'agreed_rate' => 'sometimes|numeric|min:0',
            'pay_rate' => 'sometimes|numeric|min:0',
            'status' => ['sometimes', Rule::in(AssignmentStatus::values())],
            'assignment_type' => ['sometimes', Rule::in(AssignmentType::values())],
            'shift_pattern' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (
                $this->has('agreed_rate') && $this->has('pay_rate') &&
                $this->agreed_rate < $this->pay_rate
            ) {
                $validator->errors()->add(
                    'agreed_rate',
                    'Agreed rate must be greater than or equal to pay rate'
                );
            }
        });
    }
}
