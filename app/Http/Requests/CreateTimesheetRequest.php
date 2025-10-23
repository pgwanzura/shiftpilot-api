<?php

namespace App\Http\Requests\Timesheet;

use Illuminate\Foundation\Http\FormRequest;

class CreateTimesheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Timesheet::class);
    }

    public function rules(): array
    {
        return [
            'shift_id' => 'required|exists:shifts,id',
            'employee_id' => 'required|exists:employees,id',
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'break_minutes' => 'nullable|integer|min:0',
            'hours_worked' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }
}
