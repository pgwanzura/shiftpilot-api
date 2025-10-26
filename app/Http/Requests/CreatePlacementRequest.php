<?php

namespace App\Http\Requests;

use \App\Models\Placement;
use Illuminate\Foundation\Http\FormRequest;

class CreatePlacementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Placement::class);
    }

    public function rules(): array
    {
        return [
            'employee_id' => 'required|exists:employees,id',
            'employer_id' => 'required|exists:employers,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'employee_rate' => 'nullable|numeric|min:0',
            'client_rate' => 'nullable|numeric|min:0',
        ];
    }
}
