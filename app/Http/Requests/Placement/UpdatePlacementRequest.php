<?php
// app/Http/Requests/Placement/UpdatePlacementRequest.php

namespace App\Http\Requests\Placement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\PlacementStatus;
use App\Enums\ExperienceLevel;
use App\Enums\BudgetType;
use App\Enums\ShiftPattern;
use App\Enums\TargetAgencies;

class UpdatePlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('placement'));
    }

    public function rules(): array
    {
        $placement = $this->route('placement');

        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'role_requirements' => 'sometimes|array|min:1',
            'role_requirements.*' => 'string|max:255',
            'required_qualifications' => 'nullable|array',
            'required_qualifications.*' => 'string|max:255',
            'experience_level' => [
                'sometimes',
                Rule::enum(ExperienceLevel::class)
            ],
            'background_check_required' => 'sometimes|boolean',
            'location_id' => 'sometimes|exists:locations,id',
            'location_instructions' => 'nullable|string|max:500',
            'start_date' => 'sometimes|date',
            'end_date' => 'nullable|date|after:start_date',
            'shift_pattern' => [
                'sometimes',
                Rule::enum(ShiftPattern::class)
            ],
            'recurrence_rules' => 'nullable|array',

            // Budget & Compensation
            'budget_type' => [
                'sometimes',
                Rule::enum(BudgetType::class)
            ],
            'budget_amount' => 'sometimes|numeric|min:0|max:999999.99',
            'currency' => 'sometimes|string|size:3|in:USD,GBP,EUR,CAD,AUD',
            'overtime_rules' => 'nullable|array',
            'target_agencies' => [
                'sometimes',
                Rule::enum(TargetAgencies::class)
            ],
            'specific_agency_ids' => 'nullable|array|required_if:target_agencies,specific',
            'specific_agency_ids.*' => 'exists:agencies,id',
            'response_deadline' => 'nullable|date|before:start_date',
            'selected_agency_id' => 'nullable|exists:agencies,id',
            'selected_employee_id' => 'nullable|exists:employees,id',
            'agreed_rate' => 'nullable|numeric|min:0|max:999999.99',
            'status' => [
                'sometimes',
                Rule::enum(PlacementStatus::class)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'specific_agency_ids.required_if' => 'Specific agencies are required when targeting specific agencies.',
            'specific_agency_ids.*.exists' => 'One or more selected agencies are invalid.',
            'end_date.after' => 'End date must be after the start date.',
            'response_deadline.before' => 'Response deadline must be before the start date.',
            'role_requirements.min' => 'At least one role requirement is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->transformJsonFields([
            'role_requirements',
            'required_qualifications',
            'specific_agency_ids',
            'recurrence_rules',
            'overtime_rules'
        ]);
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
            $placement = $this->route('placement');

            if (!$placement->isDraft()) {
                $nonDraftFields = [
                    'title',
                    'description',
                    'role_requirements',
                    'required_qualifications',
                    'experience_level',
                    'location_id',
                    'start_date',
                    'end_date',
                    'shift_pattern',
                    'budget_type',
                    'budget_amount',
                    'currency',
                    'target_agencies',
                    'specific_agency_ids',
                    'response_deadline'
                ];

                foreach ($nonDraftFields as $field) {
                    if ($this->has($field)) {
                        $validator->errors()->add(
                            $field,
                            "Cannot update {$field} once placement is no longer in draft status."
                        );
                    }
                }
            }

            if ($this->has('status')) {
                $this->validateStatusTransition($validator, $placement);
            }
        });
    }

    protected function validateStatusTransition($validator, $placement): void
    {
        $currentStatus = $placement->status;
        $newStatus = $this->status;

        $allowedTransitions = [
            PlacementStatus::DRAFT->value => [
                PlacementStatus::ACTIVE->value,
                PlacementStatus::CANCELLED->value,
            ],
            PlacementStatus::ACTIVE->value => [
                PlacementStatus::FILLED->value,
                PlacementStatus::CANCELLED->value,
                PlacementStatus::COMPLETED->value,
            ],
            PlacementStatus::FILLED->value => [
                PlacementStatus::COMPLETED->value,
                PlacementStatus::CANCELLED->value,
            ],
        ];

        if (
            isset($allowedTransitions[$currentStatus]) &&
            !in_array($newStatus, $allowedTransitions[$currentStatus])
        ) {
            $validator->errors()->add(
                'status',
                "Cannot transition from {$currentStatus} to {$newStatus}."
            );
        }
    }
}
