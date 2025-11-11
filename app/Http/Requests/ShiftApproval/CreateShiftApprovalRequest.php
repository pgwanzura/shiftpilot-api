<?php

namespace App\Http\Requests;

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
            'status' => 'required|in:approved,rejected',
            'signature_blob_url' => 'nullable|url',
            'notes' => 'nullable|string|max:1000'
        ];
    }
}