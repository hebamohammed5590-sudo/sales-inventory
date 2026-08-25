<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_suppliers(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->get(route('suppliers.index'))
            ->assertOk();
    }

    public function test_manager_can_view_suppliers(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->get(route('suppliers.index'))
            ->assertOk();
    }

    public function test_cashier_cannot_view_suppliers(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($cashier)
            ->get(route('suppliers.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_suppliers(): void
    {
        $this->get(route('suppliers.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_supplier(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(
                route('suppliers.store'),
                $this->supplierData()
            )
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Tech Supplies',
            'phone' => '01022222222',
        ]);
    }

    public function test_manager_can_create_supplier(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->post(
                route('suppliers.store'),
                $this->supplierData()
            )
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'phone' => '01022222222',
        ]);
    }

    public function test_cashier_cannot_create_supplier(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($cashier)
            ->post(
                route('suppliers.store'),
                $this->supplierData()
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('suppliers', [
            'phone' => '01022222222',
        ]);
    }

    public function test_supplier_phone_is_required(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(
                route('suppliers.store'),
                $this->supplierData([
                    'phone' => '',
                ])
            )
            ->assertSessionHasErrors('phone');
    }

    public function test_supplier_phone_must_have_valid_format(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(
                route('suppliers.store'),
                $this->supplierData([
                    'phone' => '35659+89+',
                ])
            )
            ->assertSessionHasErrors('phone');
    }

    public function test_supplier_phone_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Supplier::factory()->create([
            'phone' => '01022222222',
        ]);

        $this->actingAs($admin)
            ->post(
                route('suppliers.store'),
                $this->supplierData()
            )
            ->assertSessionHasErrors('phone');
    }

    public function test_customer_and_supplier_can_share_same_phone(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Customer::factory()->create([
            'phone' => '01022222222',
        ]);

        $this->actingAs($admin)
            ->post(
                route('suppliers.store'),
                $this->supplierData()
            )
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'phone' => '01022222222',
        ]);
    }

    public function test_admin_can_update_supplier(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->put(
                route('suppliers.update', $supplier),
                $this->supplierData([
                    'name' => 'Updated Supplier',
                    'phone' => $supplier->phone,
                ])
            )
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Supplier',
        ]);
    }

    public function test_cashier_cannot_update_supplier(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $supplier = Supplier::factory()->create();

        $this->actingAs($cashier)
            ->put(
                route('suppliers.update', $supplier),
                $this->supplierData([
                    'phone' => $supplier->phone,
                ])
            )
            ->assertForbidden();
    }

    public function test_admin_can_delete_supplier(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseMissing('suppliers', [
            'id' => $supplier->id,
        ]);
    }

    public function test_cashier_cannot_delete_supplier(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $supplier = Supplier::factory()->create();

        $this->actingAs($cashier)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertForbidden();

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
        ]);
    }

    public function test_suppliers_can_be_searched_by_name(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Supplier::factory()->create([
            'name' => 'Tech Supplies',
        ]);

        Supplier::factory()->create([
            'name' => 'Office Furniture',
        ]);

        $this->actingAs($admin)
            ->get(route('suppliers.index', [
                'search' => 'Tech',
            ]))
            ->assertOk()
            ->assertSee('Tech Supplies')
            ->assertDontSee('Office Furniture');
    }

    public function test_suppliers_can_be_searched_by_phone(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Supplier::factory()->create([
            'name' => 'Found Supplier',
            'phone' => '01088888888',
        ]);

        Supplier::factory()->create([
            'name' => 'Hidden Supplier',
            'phone' => '01111111111',
        ]);

        $this->actingAs($admin)
            ->get(route('suppliers.index', [
                'search' => '888888',
            ]))
            ->assertOk()
            ->assertSee('Found Supplier')
            ->assertDontSee('Hidden Supplier');
    }

    private function supplierData(array $overrides = []): array
    {
        return array_merge(
            [
                'name' => 'Tech Supplies',
                'phone' => '01022222222',
                'email' => 'supplier@example.com',
                'address' => 'Giza',
                'notes' => 'Electronics supplier',
            ],
            $overrides
        );
    }
}
