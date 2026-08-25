<?php

namespace App\Http\Requests;

use App\Models\StockAdjustment;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            StockAdjustment::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            'quantity_change' => [
                'required',
                'integer',
                'not_in:0',
            ],

            'notes' => [
                'required',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Please select a product.',

            'product_id.exists' => 'The selected product does not exist.',

            'quantity_change.required' => 'Please enter the stock adjustment quantity.',

            'quantity_change.integer' => 'The stock adjustment must be a whole number.',

            'quantity_change.not_in' => 'The stock adjustment cannot be zero.',

            'notes.required' => 'Please provide a reason for the stock adjustment.',
        ];
    }
}
