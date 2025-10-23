<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Payment::class);
    }

    public function rules(): array
    {
        return [
            'method' => 'required|in:stripe,bacs,sepa,paypal',
            'amount' => 'required|numeric|min:0',
            'payment_method_id' => 'required_if:method,stripe|string',
            'save_payment_method' => 'boolean',
        ];
    }
}
