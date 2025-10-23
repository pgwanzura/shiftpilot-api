<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimesheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('POST')) {
            return $this->user()->isEmployee() || $this->user()->isSuperAdmin();
        }

        return $this->user()->isAgencyAdmin() ||
               $this->user()->isEmployerAdmin() ||
               $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $rules = [
            'shift_id' => 'required|exists:shifts,id',
            'employee_id' => 'required|exists:employees,id',
            'clock_in' => 'nullable|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'break_minutes' => 'nullable|integer|min:0',
            'hours_worked' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,agency_approved,employer_approved,rejected',
            'agency_approved_by' => 'nullable|exists:users,id',
            'agency_approved_at' => 'nullable|date',
            'approved_by_contact_id' => 'nullable|exists:contacts,id',
            'approved_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'attachments' => 'nullable|array',
        ];

        if ($this->isMethod('POST') && $this->user()->isEmployee()) {
            return [
                'shift_id' => 'required|exists:shifts,id',
                'clock_in' => 'nullable|date',
                'clock_out' => 'nullable|date|after:clock_in',
                'break_minutes' => 'nullable|integer|min:0',
                'notes' => 'nullable|string',
                'attachments' => 'nullable|array',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'clock_out.after' => 'Clock out time must be after clock in time.',
            'employee_id.required' => 'The employee is required.',
            'shift_id.required' => 'The shift is required.',
        ];
    }
}
