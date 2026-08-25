<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        if (! $invoice instanceof Invoice) {
            return false;
        }

        return $this->user()?->can(
            'view',
            $invoice
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'regex:/^\d+(?:\.\d{1,2})?$/',
                'numeric',
                'gt:0',
            ],

            'method' => [
                'required',
                Rule::enum(
                    PaymentMethod::class
                ),
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'paid_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter the payment amount.',

            'amount.regex' => 'The payment amount must have no more than two decimal places.',

            'amount.numeric' => 'The payment amount must be a valid number.',

            'amount.gt' => 'The payment amount must be greater than zero.',

            'method.required' => 'Please select a payment method.',

            'reference.max' => 'The payment reference cannot exceed 255 characters.',

            'notes.max' => 'The payment notes cannot exceed 1000 characters.',
        ];
    }
}
