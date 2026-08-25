<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_users_page(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_manager_cannot_view_users_page(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_cashier_cannot_view_users_page(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($cashier)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_users_page(): void
    {
        $this->get(route('users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'New Cashier',
                'email' => 'new-cashier@example.com',
                'phone' => '01000000009',
                'role' => Role::Cashier->value,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-cashier@example.com',
            'role' => Role::Cashier->value,
        ]);
    }

    public function test_manager_cannot_create_user(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->post(route('users.store'), [
                'name' => 'Unauthorized User',
                'email' => 'unauthorized@example.com',
                'phone' => '01000000009',
                'role' => Role::Cashier->value,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'unauthorized@example.com',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $employee = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $employee), [
                'name' => 'Updated Name',
                'email' => $employee->email,
                'phone' => '01000000010',
                'role' => Role::Manager->value,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => true,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'Updated Name',
            'role' => Role::Manager->value,
        ]);
    }

    public function test_admin_can_deactivate_user(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $employee = User::factory()->create([
            'role' => Role::Cashier,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $employee), [
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone,
                'role' => Role::Cashier->value,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => false,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'phone' => $admin->phone,
                'role' => Role::Admin->value,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => false,
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_user_role_must_be_valid(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Invalid Role User',
                'email' => 'invalid-role@example.com',
                'phone' => '01000000011',
                'role' => 'superhero',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_user_email_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $existingUser = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Duplicate Email User',
                'email' => $existingUser->email,
                'phone' => '01000000012',
                'role' => Role::Cashier->value,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('email');
    }
}
