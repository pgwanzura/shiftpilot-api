<?php
// app/Http/Requests/Placement/CreatePlacementRequest.php

namespace App\Http\Requests\Placement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PlacementStatus;
use App\Enums\ExperienceLevel;
use App\Enums\BudgetType;
use App\Enums\ShiftPattern;
use App\Enums\TargetAgencies;
use App\Models\Placement;

class CreatePlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Placement::class);
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'required|exists:employers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'role_requirements' => 'required|array|min:1',
            'role_requirements.*' => 'string|max:255',
            'required_qualifications' => 'nullable|array',
            'required_qualifications.*' => 'string|max:255',
            'experience_level' => [
                'required',
                Rule::enum(ExperienceLevel::class)
            ],
            'background_check_required' => 'required|boolean',
            'location_id' => 'required|exists:locations,id',
            'location_instructions' => 'nullable|string|max:500',
            'start_date' => 'required|date|after:today',
            'end_date' => 'nullable|date|after:start_date',
            'shift_pattern' => [
                'required',
                Rule::enum(ShiftPattern::class)
            ],
            'recurrence_rules' => 'nullable|array',

            'budget_type' => [
                'required',
                Rule::enum(BudgetType::class)
            ],
            'budget_amount' => 'required|numeric|min:0|max:999999.99',
            'currency' => 'required|string|size:3|in:USD,GBP,EUR,CAD,AUD',
            'overtime_rules' => 'nullable|array',

            'target_agencies' => [
                'required',
                Rule::enum(TargetAgencies::class)
            ],
            'specific_agency_ids' => 'nullable|array|required_if:target_agencies,specific',
            'specific_agency_ids.*' => 'exists:agencies,id',
            'response_deadline' => 'nullable|date|after:today|before:start_date',

            'selected_agency_id' => 'nullable|exists:agencies,id',
            'selected_employee_id' => 'nullable|exists:employees,id',
            'agreed_rate' => 'nullable|numeric|min:0|max:999999.99',


            'status' => [
                'nullable',
                Rule::enum(PlacementStatus::class)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'employer_id.required' => 'Employer is required.',
            'employer_id.exists' => 'The selected employer is invalid.',
            'role_requirements.required' => 'At least one role requirement is required.',
            'role_requirements.min' => 'At least one role requirement is required.',
            'location_id.required' => 'Location is required.',
            'location_id.exists' => 'The selected location is invalid.',
            'specific_agency_ids.required_if' => 'Specific agencies are required when targeting specific agencies.',
            'specific_agency_ids.*.exists' => 'One or more selected agencies are invalid.',
            'start_date.after' => 'Start date must be in the future.',
            'end_date.after' => 'End date must be after the start date.',
            'response_deadline.before' => 'Response deadline must be before the start date.',
            'budget_amount.min' => 'Budget amount must be positive.',
            'currency.in' => 'The selected currency is not supported.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employer_id' => 'employer',
            'location_id' => 'location',
            'selected_agency_id' => 'selected agency',
            'selected_employee_id' => 'selected employee',
            'specific_agency_ids' => 'specific agencies',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Handle JSON array fields
        $this->transformJsonFields([
            'role_requirements',
            'required_qualifications',
            'specific_agency_ids',
            'recurrence_rules',
            'overtime_rules'
        ]);

        // Set default values
        $this->merge([
            'background_check_required' => $this->boolean('background_check_required', false),
            'status' => $this->status ?? PlacementStatus::DRAFT->value,
            'created_by_id' => $this->user()->id,
        ]);

        // If user is an employer, automatically set employer_id
        if ($this->user()->isEmployer() && !$this->has('employer_id')) {
            $this->merge([
                'employer_id' => $this->user()->employer->id,
            ]);
        }
    }

    protected function transformJsonFields(array $fields): void
    {
        foreach ($fields as $field) {
            if ($this->has($field) && is_string($this->$field)) {
                $decoded = json_decode($this->$field, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([$field => $decoded]);
                }
            }
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate that specific_agency_ids is not empty when target_agencies is specific
            if (
                $this->target_agencies === TargetAgencies::SPECIFIC->value &&
                empty($this->specific_agency_ids)
            ) {
                $validator->errors()->add(
                    'specific_agency_ids',
                    'At least one agency must be selected when targeting specific agencies.'
                );
            }

            // Validate that recurrence_rules is provided for recurring shift patterns
            if (
                $this->shift_pattern === ShiftPattern::RECURRING->value &&
                empty($this->recurrence_rules)
            ) {
                $validator->errors()->add(
                    'recurrence_rules',
                    'Recurrence rules are required for recurring shift patterns.'
                );
            }

            // Validate that selected_agency_id is in specific_agency_ids if provided
            if (
                $this->target_agencies === TargetAgencies::SPECIFIC->value &&
                $this->selected_agency_id &&
                !in_array($this->selected_agency_id, $this->specific_agency_ids ?? [])
            ) {
                $validator->errors()->add(
                    'selected_agency_id',
                    'Selected agency must be one of the targeted agencies.'
                );
            }

            // Validate budget constraints based on type
            if ($this->budget_type === BudgetType::HOURLY->value && $this->budget_amount > 500) {
                $validator->errors()->add(
                    'budget_amount',
                    'Hourly rate seems unusually high. Please verify the amount.'
                );
            }
        });
    }
}
