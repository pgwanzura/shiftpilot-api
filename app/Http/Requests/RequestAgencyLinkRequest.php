<?php

namespace App\Http\Requests\Employer;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\EmployerAgencyLink;

class RequestAgencyLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EmployerAgencyLink::class);
    }

    public function rules(): array
    {
        return [
            'agency_id' => 'required|exists:agencies,id',
        ];
    }
}
