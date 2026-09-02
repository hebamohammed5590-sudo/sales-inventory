<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ProductReturn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReturnPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_product_returns(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $productReturn = new ProductReturn;

        $this->assertTrue(
            $admin->can(
                'viewAny',
                ProductReturn::class
            )
        );

        $this->assertTrue(
            $admin->can(
                'view',
                $productReturn
            )
        );

        $this->assertTrue(
            $admin->can(
                'create',
                ProductReturn::class
            )
        );
    }

    public function test_manager_can_manage_product_returns(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $productReturn = new ProductReturn;

        $this->assertTrue(
            $manager->can(
                'viewAny',
                ProductReturn::class
            )
        );

        $this->assertTrue(
            $manager->can(
                'view',
                $productReturn
            )
        );

        $this->assertTrue(
            $manager->can(
                'create',
                ProductReturn::class
            )
        );
    }

    public function test_cashier_can_manage_product_returns(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $productReturn = new ProductReturn;

        $this->assertTrue(
            $cashier->can(
                'viewAny',
                ProductReturn::class
            )
        );

        $this->assertTrue(
            $cashier->can(
                'view',
                $productReturn
            )
        );

        $this->assertTrue(
            $cashier->can(
                'create',
                ProductReturn::class
            )
        );
    }

    public function test_product_returns_cannot_be_updated_or_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $productReturn = new ProductReturn;

        $this->assertFalse(
            $admin->can(
                'update',
                $productReturn
            )
        );

        $this->assertFalse(
            $admin->can(
                'delete',
                $productReturn
            )
        );
    }
}
