<?php

namespace App\Http\Requests\ShiftApproval;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\ShiftApproval::class);
    }

    public function rules(): array
    {
        return [
            'shift_id' => 'required|exists:shifts,id',
            'contact_id' => 'required|exists:contacts,id',
            'notes' => 'nullable|string',
        ];
    }
}
