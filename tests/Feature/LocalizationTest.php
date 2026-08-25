<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);
    }

    public function test_default_application_language_is_english(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('dashboard')
        );

        $response->assertOk();

        $response->assertSee(
            'lang="en"',
            false
        );

        $response->assertSee(
            'dir="ltr"',
            false
        );
    }

    public function test_authenticated_user_can_switch_to_arabic(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('locale.update', [
                'locale' => 'ar',
            ])
        );

        $response->assertRedirect();

        $response->assertSessionHas(
            'locale',
            'ar'
        );
    }

    public function test_authenticated_user_can_switch_to_english(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->post(
                route('locale.update', [
                    'locale' => 'en',
                ])
            );

        $response->assertRedirect();

        $response->assertSessionHas(
            'locale',
            'en'
        );
    }

    public function test_guest_can_switch_application_language(): void
    {
        $response = $this->post(
            route('locale.update', [
                'locale' => 'ar',
            ])
        );

        $response->assertRedirect();

        $response->assertSessionHas(
            'locale',
            'ar'
        );
    }

    public function test_unsupported_language_returns_not_found(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('locale.update', [
                'locale' => 'fr',
            ])
        );

        $response->assertNotFound();
    }

    public function test_arabic_dashboard_uses_rtl_direction(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'lang="ar"',
            false
        );

        $response->assertSee(
            'dir="rtl"',
            false
        );
    }

    public function test_english_dashboard_uses_ltr_direction(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'en',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'lang="en"',
            false
        );

        $response->assertSee(
            'dir="ltr"',
            false
        );
    }

    public function test_arabic_dashboard_displays_translated_heading(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'لوحة التحكم'
        );
    }

    public function test_arabic_translation_file_contains_core_translations(): void
    {
        app()->setLocale(
            'ar'
        );

        $this->assertSame(
            'لوحة التحكم',
            __('Dashboard')
        );

        $this->assertSame(
            'المنتجات',
            __('Products')
        );

        $this->assertSame(
            'العملاء',
            __('Customers')
        );

        $this->assertSame(
            'الموردون',
            __('Suppliers')
        );

        $this->assertSame(
            'الإعدادات',
            __('Settings')
        );
    }

    public function test_invalid_session_locale_falls_back_to_english(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'invalid',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'lang="en"',
            false
        );

        $response->assertSee(
            'dir="ltr"',
            false
        );
    }

    public function test_navigation_displays_arabic_language_switcher(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'en',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'العربية'
        );

        $response->assertSee(
            route('locale.update', [
                'locale' => 'ar',
            ])
        );
    }

    public function test_navigation_displays_english_language_switcher(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'English'
        );

        $response->assertSee(
            route('locale.update', [
                'locale' => 'en',
            ])
        );
    }

    public function test_arabic_login_page_uses_rtl_direction(): void
    {
        $response = $this
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(
                route('login')
            );

        $response->assertOk();

        $response->assertSee(
            'lang="ar"',
            false
        );

        $response->assertSee(
            'dir="rtl"',
            false
        );
    }

    public function test_language_selection_persists_between_requests(): void
    {
        $this->actingAs(
            $this->admin
        );

        $this->post(
            route('locale.update', [
                'locale' => 'ar',
            ])
        )->assertSessionHas(
            'locale',
            'ar'
        );

        $response = $this->get(
            route('dashboard')
        );

        $response->assertOk();

        $response->assertSee(
            'lang="ar"',
            false
        );

        $response->assertSee(
            'لوحة التحكم'
        );
    }
}
