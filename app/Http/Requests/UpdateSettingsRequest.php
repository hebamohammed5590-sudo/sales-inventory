<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === Role::Admin;
    }

    public function rules(): array
    {
        return [
            'company_name' => [
                'required',
                'string',
                'max:255',
            ],

            'company_phone' => [
                'nullable',
                'string',
                'max:30',
                'regex:/^[0-9+\-\s()]+$/',
            ],

            'company_address' => [
                'nullable',
                'string',
                'max:500',
            ],

            'currency_symbol' => [
                'required',
                'string',
                'max:10',
            ],

            'tax_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                'decimal:0,2',
            ],

            'low_stock_threshold' => [
                'required',
                'integer',
                'min:0',
            ],

            'invoice_prefix' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9-]+$/',
            ],

            'purchase_invoice_prefix' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9-]+$/',
            ],

            'company_logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }
}
