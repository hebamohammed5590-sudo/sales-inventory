<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsWebTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'role' => Role::Manager,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => Role::Cashier,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.edit'));

        $response->assertOk();
        $response->assertViewIs('settings.edit');
        $response->assertSee('Company Information');
        $response->assertSee('Financial Settings');
        $response->assertSee('Inventory Settings');
    }

    public function test_manager_cannot_view_settings_page(): void
    {
        $response = $this->actingAs($this->manager)->get(route('settings.edit'));
        $response->assertForbidden();
    }

    public function test_cashier_cannot_view_settings_page(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('settings.edit'));
        $response->assertForbidden();
    }

    public function test_guest_cannot_view_settings_page(): void
    {
        $response = $this->get(route('settings.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_update_company_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings([
                'company_name' => 'Heba Trading',
                'company_phone' => '01012345678',
                'company_address' => 'Alexandria, Egypt',
            ])
        );

        $response->assertRedirect(route('settings.edit'));
        $response->assertSessionHas('success');

        $this->assertSame('Heba Trading', Setting::get('company_name'));
        $this->assertSame('01012345678', Setting::get('company_phone'));
        $this->assertSame('Alexandria, Egypt', Setting::get('company_address'));
    }

    public function test_admin_can_update_financial_settings(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings([
                'currency_symbol' => 'USD',
                'tax_rate' => '15.50',
                'invoice_prefix' => 'SALE',
                'purchase_invoice_prefix' => 'BUY',
            ])
        );

        $response->assertRedirect(route('settings.edit'));

        $this->assertSame('USD', Setting::get('currency_symbol'));
        $this->assertSame('15.50', Setting::get('tax_rate'));
        $this->assertSame('SALE', Setting::get('invoice_prefix'));
        $this->assertSame('BUY', Setting::get('purchase_invoice_prefix'));
    }

    public function test_admin_can_update_low_stock_threshold(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings([
                'low_stock_threshold' => 12,
            ])
        );

        $response->assertRedirect(route('settings.edit'));
        $this->assertSame('12', Setting::get('low_stock_threshold'));
    }

    public function test_manager_cannot_update_settings(): void
    {
        $response = $this->actingAs($this->manager)->put(route('settings.update'), $this->validSettings());
        $response->assertForbidden();
    }

    public function test_cashier_cannot_update_settings(): void
    {
        $response = $this->actingAs($this->cashier)->put(route('settings.update'), $this->validSettings());
        $response->assertForbidden();
    }

    public function test_guest_cannot_update_settings(): void
    {
        $response = $this->put(route('settings.update'), $this->validSettings());
        $response->assertRedirect(route('login'));
    }

    public function test_company_name_is_required(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['company_name' => ''])
        );

        $response->assertSessionHasErrors('company_name');
    }

    public function test_company_phone_must_have_valid_format(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['company_phone' => 'invalid-phone!'])
        );

        $response->assertSessionHasErrors('company_phone');
    }

    public function test_tax_rate_cannot_be_negative(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['tax_rate' => '-1'])
        );

        $response->assertSessionHasErrors('tax_rate');
    }

    public function test_tax_rate_cannot_exceed_one_hundred(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['tax_rate' => '101'])
        );

        $response->assertSessionHasErrors('tax_rate');
    }

    public function test_low_stock_threshold_cannot_be_negative(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['low_stock_threshold' => -1])
        );

        $response->assertSessionHasErrors('low_stock_threshold');
    }

    public function test_invoice_prefix_must_have_valid_format(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['invoice_prefix' => 'INVALID PREFIX!'])
        );

        $response->assertSessionHasErrors('invoice_prefix');
    }

    public function test_admin_can_upload_company_logo(): void
    {
        $disk = Storage::fake('public');
        $logo = UploadedFile::fake()->image('company-logo.png');

        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['company_logo' => $logo])
        );

        $response->assertRedirect(route('settings.edit'));

        $storedLogo = Setting::get('company_logo');
        $this->assertNotEmpty($storedLogo);
        $disk->assertExists($storedLogo);
    }

    public function test_uploading_new_logo_deletes_old_logo(): void
    {
        $disk = Storage::fake('public');
        $oldLogo = UploadedFile::fake()->image('old-logo.png')->store('company', 'public');
        Setting::set('company_logo', $oldLogo);

        $newLogo = UploadedFile::fake()->image('new-logo.png');

        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['company_logo' => $newLogo])
        );

        $response->assertRedirect(route('settings.edit'));

        $storedLogo = Setting::get('company_logo');
        $this->assertNotSame($oldLogo, $storedLogo);
        $disk->assertMissing($oldLogo);
        $disk->assertExists($storedLogo);
    }

    public function test_company_logo_must_be_an_image(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->put(
            route('settings.update'),
            $this->validSettings(['company_logo' => $file])
        );

        $response->assertSessionHasErrors('company_logo');
    }

    public function test_settings_page_shows_existing_values(): void
    {
        Setting::set('company_name', 'Existing Company');
        Setting::set('currency_symbol', 'SAR');
        Setting::set('invoice_prefix', 'CUSTOM');

        $response = $this->actingAs($this->admin)->get(route('settings.edit'));

        $response->assertOk();
        $response->assertSee('Existing Company');
        $response->assertSee('SAR');
        $response->assertSee('CUSTOM');
    }

    private function validSettings(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Sales Inventory',
            'company_phone' => '01000000000',
            'company_address' => 'Cairo, Egypt',
            'currency_symbol' => 'EGP',
            'tax_rate' => '14',
            'low_stock_threshold' => 5,
            'invoice_prefix' => 'INV',
            'purchase_invoice_prefix' => 'PUR',
        ], $overrides);
    }
}
