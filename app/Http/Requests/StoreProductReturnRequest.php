<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $invoice = $this->route('invoice');

        $invoiceDate = $invoice instanceof Invoice
            ? $invoice->invoice_date->format('Y-m-d')
            : null;

        return [
            'return_date' => array_values(array_filter([
                'nullable',
                'date',
                'before_or_equal:today',
                $invoiceDate
                    ? 'after_or_equal:'.$invoiceDate
                    : null,
            ])),

            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'return_date.after_or_equal' => __(
                'Return date cannot be before the invoice date.'
            ),
        ];
    }
}
