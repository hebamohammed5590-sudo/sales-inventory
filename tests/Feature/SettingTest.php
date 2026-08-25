<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_setting_can_be_saved_and_retrieved(): void
    {
        Setting::set('company_name', 'My Company');

        $this->assertSame(
            'My Company',
            Setting::get('company_name')
        );

        $this->assertDatabaseHas('settings', [
            'key' => 'company_name',
            'value' => 'My Company',
        ]);
    }

    public function test_missing_setting_returns_default_value(): void
    {
        $value = Setting::get(
            'missing_setting',
            'default value'
        );

        $this->assertSame(
            'default value',
            $value
        );
    }

    public function test_existing_setting_can_be_updated(): void
    {
        Setting::set('tax_rate', '14');

        Setting::set('tax_rate', '15');

        $this->assertSame(
            '15',
            Setting::get('tax_rate')
        );

        $this->assertDatabaseCount(
            'settings',
            1
        );
    }

    public function test_setting_is_cached_after_reading(): void
    {
        Setting::set('currency_symbol', 'EGP');

        $this->assertFalse(
            Cache::has('settings.currency_symbol')
        );

        Setting::get('currency_symbol');

        $this->assertTrue(
            Cache::has('settings.currency_symbol')
        );
    }

    public function test_cache_is_cleared_when_setting_is_updated(): void
    {
        Setting::set('tax_rate', '14');

        Setting::get('tax_rate');

        $this->assertTrue(
            Cache::has('settings.tax_rate')
        );

        Setting::set('tax_rate', '15');

        $this->assertFalse(
            Cache::has('settings.tax_rate')
        );

        $this->assertSame(
            '15',
            Setting::get('tax_rate')
        );
    }

    public function test_cache_is_cleared_when_setting_is_deleted(): void
    {
        Setting::set('company_phone', '01000000000');

        Setting::get('company_phone');

        $this->assertTrue(
            Cache::has('settings.company_phone')
        );

        $setting = Setting::query()
            ->where('key', 'company_phone')
            ->firstOrFail();

        $setting->delete();

        $this->assertFalse(
            Cache::has('settings.company_phone')
        );
    }

    public function test_settings_seeder_creates_required_settings(): void
    {
        $this->seed(SettingsSeeder::class);

        $expectedKeys = [
            'company_name',
            'company_phone',
            'company_address',
            'company_logo',
            'currency_symbol',
            'tax_rate',
            'invoice_prefix',
            'low_stock_threshold',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
            ]);
        }

        $this->assertSame(
            'EGP',
            Setting::get('currency_symbol')
        );

        $this->assertSame(
            '14',
            Setting::get('tax_rate')
        );
    }
}
