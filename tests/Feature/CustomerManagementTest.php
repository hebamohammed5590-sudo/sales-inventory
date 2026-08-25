<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_customers(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk();
    }

    public function test_manager_can_view_customers(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->get(route('customers.index'))
            ->assertOk();
    }

    public function test_cashier_can_view_customers(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($cashier)
            ->get(route('customers.index'))
            ->assertOk();
    }

    public function test_guest_cannot_view_customers(): void
    {
        $this->get(route('customers.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_customer(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(
                route('customers.store'),
                $this->customerData()
            )
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'Ahmed Hassan',
            'phone' => '01011111111',
        ]);
    }

    public function test_manager_can_create_customer(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->post(
                route('customers.store'),
                $this->customerData()
            )
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'phone' => '01011111111',
        ]);
    }

    public function test_cashier_cannot_create_customer(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($cashier)
            ->post(
                route('customers.store'),
                $this->customerData()
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('customers', [
            'phone' => '01011111111',
        ]);
    }

    public function test_customer_phone_is_required(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(
                route('customers.store'),
                $this->customerData([
                    'phone' => '',
                ])
            )
            ->assertSessionHasErrors('phone');
    }

    public function test_customer_phone_must_have_valid_format(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(
                route('customers.store'),
                $this->customerData([
                    'phone' => '56+5+',
                ])
            )
            ->assertSessionHasErrors('phone');
    }

    public function test_customer_phone_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Customer::factory()->create([
            'phone' => '01011111111',
        ]);

        $this->actingAs($admin)
            ->post(
                route('customers.store'),
                $this->customerData()
            )
            ->assertSessionHasErrors('phone');
    }

    public function test_admin_can_update_customer(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->put(
                route('customers.update', $customer),
                $this->customerData([
                    'name' => 'Updated Customer',
                    'phone' => $customer->phone,
                ])
            )
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
        ]);
    }

    public function test_cashier_cannot_update_customer(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $customer = Customer::factory()->create();

        $this->actingAs($cashier)
            ->put(
                route('customers.update', $customer),
                $this->customerData([
                    'phone' => $customer->phone,
                ])
            )
            ->assertForbidden();
    }

    public function test_admin_can_delete_customer(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $customer = Customer::factory()->create();

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_cashier_cannot_delete_customer(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $customer = Customer::factory()->create();

        $this->actingAs($cashier)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_customers_can_be_searched_by_name(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Customer::factory()->create([
            'name' => 'Ahmed Hassan',
        ]);

        Customer::factory()->create([
            'name' => 'Sara Mohamed',
        ]);

        $this->actingAs($admin)
            ->get(route('customers.index', [
                'search' => 'Ahmed',
            ]))
            ->assertOk()
            ->assertSee('Ahmed Hassan')
            ->assertDontSee('Sara Mohamed');
    }

    public function test_customers_can_be_searched_by_phone(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Customer::factory()->create([
            'name' => 'Found Customer',
            'phone' => '01099999999',
        ]);

        Customer::factory()->create([
            'name' => 'Hidden Customer',
            'phone' => '01111111111',
        ]);

        $this->actingAs($admin)
            ->get(route('customers.index', [
                'search' => '999999',
            ]))
            ->assertOk()
            ->assertSee('Found Customer')
            ->assertDontSee('Hidden Customer');
    }

    private function customerData(array $overrides = []): array
    {
        return array_merge(
            [
                'name' => 'Ahmed Hassan',
                'phone' => '01011111111',
                'email' => 'ahmed@example.com',
                'address' => 'Cairo',
                'notes' => 'Regular customer',
            ],
            $overrides
        );
    }
}
