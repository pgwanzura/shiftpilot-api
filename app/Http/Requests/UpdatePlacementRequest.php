<?php

namespace App\Http\Requests\Agency;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('placement'));
    }

    public function rules(): array
    {
        return [
            'end_date' => 'nullable|date|after:start_date',
            'employee_rate' => 'nullable|numeric|min:0',
            'client_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,completed,terminated',
            'notes' => 'nullable|string',
        ];
    }
}
