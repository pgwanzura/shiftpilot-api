<?php
// app/Http/Requests/ChangeAssignmentStatusRequest.php

namespace App\Http\Requests\Assignment;

use App\Enums\AssignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeAssignmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('assignment'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(AssignmentStatus::values())],
            'reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status provided',
        ];
    }
}