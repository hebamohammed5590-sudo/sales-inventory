<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrudPageAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $cashier;

    private Category $category;

    private Product $product;

    private Customer $customer;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->category = Category::factory()->create();

        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'quantity' => 10,
        ]);

        $this->customer = Customer::factory()->create();

        $this->supplier = Supplier::factory()->create();
    }

    public function test_admin_can_open_product_crud_pages(): void
    {
        $this->actingAs($this->admin)
            ->get(route('products.create'))
            ->assertOk();

        $this->get(route('products.show', $this->product))
            ->assertOk();

        $this->get(route('products.edit', $this->product))
            ->assertOk();
    }

    public function test_admin_can_open_category_crud_pages(): void
    {
        $this->actingAs($this->admin)
            ->get(route('categories.create'))
            ->assertOk();

        $this->get(route('categories.edit', $this->category))
            ->assertOk();
    }

    public function test_admin_can_open_customer_crud_pages(): void
    {
        $this->actingAs($this->admin)
            ->get(route('customers.create'))
            ->assertOk();

        $this->get(route('customers.show', $this->customer))
            ->assertOk();

        $this->get(route('customers.edit', $this->customer))
            ->assertOk();
    }

    public function test_admin_can_open_supplier_crud_pages(): void
    {
        $this->actingAs($this->admin)
            ->get(route('suppliers.create'))
            ->assertOk();

        $this->get(route('suppliers.show', $this->supplier))
            ->assertOk();

        $this->get(route('suppliers.edit', $this->supplier))
            ->assertOk();
    }

    public function test_admin_can_open_user_crud_pages(): void
    {
        $employee = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($this->admin)
            ->get(route('users.create'))
            ->assertOk();

        $this->get(route('users.edit', $employee))
            ->assertOk();
    }

    public function test_admin_can_open_stock_adjustment_create_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('stock-adjustments.create'))
            ->assertOk();
    }

    public function test_admin_can_open_sales_invoice_create_and_show_pages(): void
    {
        $invoice = app(InvoiceService::class)->create(
            InvoiceType::Sale,
            $this->admin,
            [
                'customer_id' => $this->customer->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
            ]
        );

        $this->actingAs($this->admin)
            ->get(route('invoices.create', [
                'type' => InvoiceType::Sale->value,
            ]))
            ->assertOk();

        $this->get(route('invoices.show', [
            'type' => InvoiceType::Sale->value,
            'invoice' => $invoice,
        ]))
            ->assertOk();
    }

    public function test_admin_can_open_purchase_invoice_create_and_show_pages(): void
    {
        $invoice = app(InvoiceService::class)->create(
            InvoiceType::Purchase,
            $this->admin,
            [
                'supplier_id' => $this->supplier->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                    ],
                ],
            ]
        );

        $this->actingAs($this->admin)
            ->get(route('invoices.create', [
                'type' => InvoiceType::Purchase->value,
            ]))
            ->assertOk();

        $this->get(route('invoices.show', [
            'type' => InvoiceType::Purchase->value,
            'invoice' => $invoice,
        ]))
            ->assertOk();
    }

    public function test_cashier_cannot_open_restricted_crud_pages(): void
    {
        $this->actingAs($this->cashier)
            ->get(route('products.create'))
            ->assertForbidden();

        $this->get(route('categories.create'))
            ->assertForbidden();

        $this->get(route('suppliers.create'))
            ->assertForbidden();

        $this->get(route('stock-adjustments.create'))
            ->assertForbidden();

        $this->get(route('users.create'))
            ->assertForbidden();

        $this->get(route('invoices.create', [
            'type' => InvoiceType::Purchase->value,
        ]))
            ->assertForbidden();
    }
}
