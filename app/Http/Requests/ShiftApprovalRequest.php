<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEmployerAdmin() || $this->user()->isContact() || $this->user()->isAgent() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'shift_id' => 'required|exists:shifts,id',
            'contact_id' => 'required|exists:contacts,id',
            'status' => 'required|in:pending,approved,rejected',
            'signed_at' => 'nullable|date',
            'signature_blob_url' => 'nullable|url',
            'notes' => 'nullable|string',
        ];
    }
}
