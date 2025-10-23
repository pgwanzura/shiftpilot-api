<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TimeOffRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEmployee() || $this->user()->isAgencyAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'type' => 'required|in:vacation,sick,personal,bereavement,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'status' => 'nullable|in:pending,approved,rejected,cancelled',
            'reason' => 'nullable|string',
            'approved_by_id' => 'nullable|exists:users,id',
            'attachments' => 'nullable|array',
        ];
    }
}
