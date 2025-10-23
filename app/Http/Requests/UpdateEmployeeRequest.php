<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    public function rules(): array
    {
        return [
            'position' => 'nullable|string|max:255',
            'pay_rate' => 'nullable|numeric|min:0',
            'availability' => 'nullable|array',
            'qualifications' => 'nullable|array',
            'employment_type' => 'nullable|in:temp,perm,part_time',
            'status' => 'nullable|in:active,inactive,suspended',
        ];
    }
}
