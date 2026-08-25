<?php

namespace App\Http\Requests;

use App\Enums\DiscountType;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->invoiceType();

        if ($type === null) {
            return false;
        }

        return $this->user()?->can(
            'create',
            [
                Invoice::class,
                $type,
            ]
        ) ?? false;
    }

    public function rules(): array
    {
        $type = $this->invoiceType();

        return [
            'customer_id' => [
                Rule::requiredIf(
                    $type === InvoiceType::Sale
                ),
                'nullable',
                'integer',
                'exists:customers,id',
            ],

            'supplier_id' => [
                Rule::requiredIf(
                    $type === InvoiceType::Purchase
                ),
                'nullable',
                'integer',
                'exists:suppliers,id',
            ],

            'invoice_date' => [
                'required',
                'date',
            ],

            'discount_type' => [
                'nullable',
                Rule::enum(DiscountType::class),
            ],

            'discount_value' => [
                'nullable',
                'regex:/^\d+(?:\.\d{1,2})?$/',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Please select a customer.',

            'supplier_id.required' => 'Please select a supplier.',

            'items.required' => 'Add at least one invoice item.',

            'items.min' => 'Add at least one invoice item.',

            'items.max' => 'An invoice cannot contain more than 50 items.',

            'items.*.product_id.required' => 'Select a product for every item.',

            'items.*.product_id.distinct' => 'The same product cannot appear more than once.',

            'items.*.product_id.exists' => 'One of the selected products does not exist.',

            'items.*.quantity.required' => 'Enter a quantity for every item.',

            'items.*.quantity.integer' => 'Each quantity must be a whole number.',

            'items.*.quantity.min' => 'Each quantity must be at least one.',

            'discount_value.regex' => 'The discount must be a valid non-negative number with no more than two decimal places.',
        ];
    }

    public function invoiceType(): ?InvoiceType
    {
        $type = $this->route('type');

        if ($type instanceof InvoiceType) {
            return $type;
        }

        return InvoiceType::tryFrom(
            (string) $type
        );
    }
}
