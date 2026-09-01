<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DarkModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_layout_contains_persistent_theme_bootstrap(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('dashboard'));

        $response->assertOk();

        $response->assertSee(
            "localStorage.getItem('theme')",
            false
        );

        $response->assertSee(
            "document.documentElement.classList.toggle('dark'",
            false
        );
    }

    public function test_navigation_contains_dark_mode_controls(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('dashboard'));

        $response->assertOk();

        $response->assertSeeText('Dark Mode');
        $response->assertSeeText('Light Mode');

        $response->assertSee(
            '$store.theme.toggle()',
            false
        );
    }

    public function test_guest_layout_contains_persistent_theme_bootstrap(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();

        $response->assertSee(
            "localStorage.getItem('theme')",
            false
        );

        $response->assertSee(
            "document.documentElement.classList.toggle('dark'",
            false
        );
    }

    public function test_dark_mode_controls_work_with_arabic_rtl_layout(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession([
                'locale' => 'ar',
            ])
            ->get(route('dashboard'));

        $response->assertOk();

        $response->assertSee(
            'dir="rtl"',
            false
        );

        $response->assertSeeText('الوضع الداكن');
        $response->assertSeeText('الوضع الفاتح');
    }
}
