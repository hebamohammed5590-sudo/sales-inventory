<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\SellPriceNotBelowCost;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            'create',
            Product::class
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'sku' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,sku',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'cost_price' => [
                'bail',
                'required',
                'string',
                'regex:/^\d{1,16}(?:\.\d{1,2})?$/',
            ],

            'sell_price' => [
                'bail',
                'required',
                'string',
                'regex:/^\d{1,16}(?:\.\d{1,2})?$/',
                new SellPriceNotBelowCost,
            ],

            'quantity' => [
                'prohibited',
            ],

            'reorder_level' => [
                'required',
                'integer',
                'min:0',
            ],

            'image' => [
                'nullable',
                'image',
                'max:2048',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
