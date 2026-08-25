<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'company_name' => 'Sales Inventory',

            'company_phone' => '01000000000',

            'company_address' => 'Cairo, Egypt',

            'company_logo' => null,

            'currency_symbol' => 'EGP',

            'tax_rate' => '14',

            'invoice_prefix' => 'INV',

            'low_stock_threshold' => '5',

            'purchase_invoice_prefix' => 'PUR',
        ];

        foreach ($settings as $key => $value) {
            Setting::set($key, $value);
        }
    }
}
