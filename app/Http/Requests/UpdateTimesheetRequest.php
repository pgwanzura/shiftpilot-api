<?php

namespace App\Http\Requests\Timesheet;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimesheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('timesheet'));
    }

    public function rules(): array
    {
        return [
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'break_minutes' => 'nullable|integer|min:0',
            'hours_worked' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:pending,agency_approved,employer_approved,rejected',
        ];
    }
}
