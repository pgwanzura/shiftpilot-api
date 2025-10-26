<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pay', $this->route('invoice'));
    }

    public function rules(): array
    {
        return [
            'method' => 'required|in:stripe,bacs,sepa,paypal',
            'amount' => 'required|numeric|min:0',
        ];
    }
}
