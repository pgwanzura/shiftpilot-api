<?php

namespace App\Http\Requests;

use App\Models\Shift;
use App\Enums\ShiftStatus;
use Illuminate\Foundation\Http\FormRequest;

class CreateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Shift::class);
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'required|exists:employers,id',
            'location_id' => 'required|exists:locations,id',
            'start_time' => 'required|date|after_or_equal:now',
            'end_time' => 'required|date|after:start_time',
            'hourly_rate' => 'nullable|numeric|min:0',
            'agency_id' => 'nullable|exists:agencies,id',
            'placement_id' => 'nullable|exists:placements,id',
            'employee_id' => 'nullable|exists:employees,id',
            'agent_id' => 'nullable|exists:agents,id',
            'status' => 'nullable|string|in:' . implode(',', ShiftStatus::values()),
            'meta' => 'nullable|array',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    protected function prepareForValidation()
    {
        if (!$this->has('status')) {
            $this->merge([
                'status' => ShiftStatus::PENDING->value,
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'The end time must be after the start time.',
            'status.in' => 'The selected status is invalid. Valid statuses: ' . implode(', ', ShiftStatus::values()),
        ];
    }
}
