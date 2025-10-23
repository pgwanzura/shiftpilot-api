<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAgencyAdmin() || $this->user()->isSuperAdmin() || $this->user()->id === $this->employee?->user_id;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'agency_id' => 'nullable|exists:agencies,id',
            'employer_id' => 'nullable|exists:employers,id',
            'position' => 'nullable|string|max:255',
            'pay_rate' => 'nullable|numeric|min:0',
            'availability' => 'nullable|array',
            'qualifications' => 'nullable|array',
            'employment_type' => 'nullable|string|in:temp,perm,part_time',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'meta' => 'nullable|array',
        ];
    }
}
