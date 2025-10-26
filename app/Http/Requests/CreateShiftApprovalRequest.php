<?php

namespace App\Http\Requests;

use \App\Models\ShiftApproval;

use Illuminate\Foundation\Http\FormRequest;

class CreateShiftApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ShiftApproval::class);
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
