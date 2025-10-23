<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmployerAgencyLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAgencyAdmin() || $this->user()->isEmployerAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'required|exists:employers,id',
            'agency_id' => 'required|exists:agencies,id',
            'status' => 'nullable|in:pending,approved,suspended,terminated',
            'contract_document_url' => 'nullable|url',
            'contract_start' => 'nullable|date',
            'contract_end' => 'nullable|date|after:contract_start',
            'terms' => 'nullable|string',
        ];
    }
}
