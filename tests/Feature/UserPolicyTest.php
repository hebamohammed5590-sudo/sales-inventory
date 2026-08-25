<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $employee = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->assertTrue(
            $admin->can('viewAny', User::class)
        );

        $this->assertTrue(
            $admin->can('view', $employee)
        );

        $this->assertTrue(
            $admin->can('create', User::class)
        );

        $this->assertTrue(
            $admin->can('update', $employee)
        );
    }

    public function test_manager_cannot_manage_users(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $employee = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->assertFalse(
            $manager->can('viewAny', User::class)
        );

        $this->assertFalse(
            $manager->can('create', User::class)
        );

        $this->assertFalse(
            $manager->can('update', $employee)
        );
    }

    public function test_cashier_cannot_manage_users(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $employee = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->assertFalse(
            $cashier->can('viewAny', User::class)
        );

        $this->assertFalse(
            $cashier->can('create', User::class)
        );

        $this->assertFalse(
            $cashier->can('update', $employee)
        );
    }

    public function test_users_cannot_be_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $employee = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->assertFalse(
            $admin->can('delete', $employee)
        );
    }
}
