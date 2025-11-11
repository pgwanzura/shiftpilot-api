<?php
// app/Http/Requests/ExtendAssignmentRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('extend', $this->route('assignment'));
    }

    public function rules(): array
    {
        return [
            'end_date' => 'required|date|after:start_date',
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
