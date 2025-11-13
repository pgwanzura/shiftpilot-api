<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RespondToShiftOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('employee') && $this->user()->employee;
    }

    public function rules(): array
    {
        return [
            'accept' => [
                'required',
                'boolean',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'accept.required' => 'Please specify whether you accept or reject the shift offer.',
            'accept.boolean' => 'The accept field must be true or false.',
            'notes.max' => 'Response notes cannot exceed 500 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'accept' => 'accept offer',
            'notes' => 'response notes',
        ];
    }

    public function prepareForValidation(): void
    {
        if ($this->has('accept') && is_string($this->accept)) {
            $this->merge([
                'accept' => $this->accept === 'true' || $this->accept === '1',
            ]);
        }
    }
}
