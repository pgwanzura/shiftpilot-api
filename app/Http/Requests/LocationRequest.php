<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEmployerAdmin() || $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        return [
            'employer_id' => 'required|exists:employers,id',
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'meta' => 'nullable|array',
        ];
    }
}
