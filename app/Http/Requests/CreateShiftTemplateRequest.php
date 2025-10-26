<?php

namespace App\Http\Requests;

use App\Models\ShiftTemplate;
use App\Enums\DayOfWeek;
use App\Enums\RecurrenceType;
use App\Enums\ShiftTemplateStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateShiftTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ShiftTemplate::class);
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'required|exists:employers,id',
            'location_id' => 'required|exists:locations,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'day_of_week' => ['required', Rule::in(DayOfWeek::values())],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'role_requirement' => 'nullable|string|max:255',
            'required_qualifications' => 'nullable|array',
            'required_qualifications.*' => 'string|max:100',
            'hourly_rate' => 'nullable|numeric|min:0|max:1000',
            'recurrence_type' => ['required', Rule::in(RecurrenceType::values())],
            'status' => ['nullable', Rule::in(ShiftTemplateStatus::values())],
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'meta' => 'nullable|array',
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->has('status')) {
            $this->merge([
                'status' => ShiftTemplateStatus::ACTIVE->value,
            ]);
        }

        if ($this->has('required_qualifications') && is_string($this->required_qualifications)) {
            $this->merge([
                'required_qualifications' => json_decode($this->required_qualifications, true) ?? [],
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'day_of_week.in' => 'The day of week must be one of: ' . implode(', ', DayOfWeek::values()),
            'recurrence_type.in' => 'The recurrence type must be one of: ' . implode(', ', RecurrenceType::values()),
            'end_time.after' => 'The end time must be after the start time.',
            'end_date.after' => 'The end date must be after the start date.',
            'required_qualifications.*.string' => 'Each qualification must be a string.',
            'required_qualifications.*.max' => 'Each qualification must not exceed 100 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'employer_id' => 'employer',
            'location_id' => 'location',
            'day_of_week' => 'day of week',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'recurrence_type' => 'recurrence type',
            'start_date' => 'start date',
            'end_date' => 'end date',
        ];
    }
}
