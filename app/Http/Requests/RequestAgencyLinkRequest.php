<?php

namespace App\Http\Requests;

use App\Models\EmployerAgencyLink;
use Illuminate\Foundation\Http\FormRequest;

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
