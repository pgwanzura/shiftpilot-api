<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Shift;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shift = Shift::findOrFail($this->route('shift'));
        return $this->user()->can('update', $shift);
    }

    public function rules(): array
    {
        return [
            'location_id' => 'sometimes|exists:locations,id',
            'start_time' => 'sometimes|date',
            'end_time' => 'sometimes|date|after:start_time',
            'hourly_rate' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'meta' => 'nullable|array',
            'status' => 'sometimes|in:scheduled,in_progress,completed,cancelled,no_show'
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.exists' => 'Selected location does not exist.',
            'start_time.date' => 'Start time must be a valid date.',
            'end_time.date' => 'End time must be a valid date.',
            'end_time.after' => 'End time must be after start time.',
            'hourly_rate.numeric' => 'Hourly rate must be a number.',
            'hourly_rate.min' => 'Hourly rate must be at least 0.',
            'status.in' => 'Status must be one of: scheduled, in_progress, completed, cancelled, no_show.'
        ];
    }
}
