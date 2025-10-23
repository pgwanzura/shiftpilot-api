<?php

namespace App\Http\Requests\Agency;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class SubmitEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'experience' => 'required|string',
            'skills' => 'required|array',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ];
    }
}
