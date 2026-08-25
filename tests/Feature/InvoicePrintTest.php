<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePrintTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    private InvoiceService $invoiceService;

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

        $this->customer = Customer::factory()->create([
            'name' => 'Test Customer',
        ]);

        $this->supplier = Supplier::factory()->create([
            'name' => 'Test Supplier',
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
        ]);

        $this->invoiceService = app(
            InvoiceService::class
        );
    }

    public function test_admin_can_print_sales_invoice(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this->actingAs($this->admin)->get(
            route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertOk();

        $response->assertViewIs(
            'invoices.print'
        );

        $response->assertSee(
            $invoice->invoice_number
        );

        $response->assertSee(
            'Sales Invoice'
        );
    }

    public function test_manager_can_print_purchase_invoice(): void
    {
        $invoice = $this->createPurchaseInvoice();

        $response = $this->actingAs($this->manager)->get(
            route('invoices.print', [
                'type' => InvoiceType::Purchase->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertOk();

        $response->assertViewIs(
            'invoices.print'
        );

        $response->assertSee(
            'Purchase Invoice'
        );

        $response->assertSee(
            'Test Supplier'
        );
    }

    public function test_cashier_can_print_sales_invoice(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this->actingAs($this->cashier)->get(
            route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertOk();

        $response->assertSee(
            $invoice->invoice_number
        );
    }

    public function test_cashier_cannot_print_purchase_invoice(): void
    {
        $invoice = $this->createPurchaseInvoice();

        $response = $this->actingAs($this->cashier)->get(
            route('invoices.print', [
                'type' => InvoiceType::Purchase->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertForbidden();
    }

    public function test_guest_cannot_print_invoice(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this->get(
            route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_invoice_type_mismatch_returns_not_found(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this->actingAs($this->admin)->get(
            route('invoices.print', [
                'type' => InvoiceType::Purchase->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertNotFound();
    }

    public function test_printed_invoice_shows_product_details(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this->actingAs($this->admin)->get(
            route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertOk();

        $response->assertSee(
            'Test Product'
        );

        $response->assertSee(
            $this->product->sku
        );

        $response->assertSee(
            '100.00'
        );

        $response->assertSee(
            '200.00'
        );
    }

    public function test_printed_sales_invoice_shows_customer(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this->actingAs($this->manager)->get(
            route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertOk();

        $response->assertSee(
            'Customer Information'
        );

        $response->assertSee(
            'Test Customer'
        );
    }

    public function test_printed_invoice_uses_company_settings(): void
    {
        Setting::set(
            'company_name',
            'Heba Trading Company'
        );

        Setting::set(
            'company_phone',
            '01000000000'
        );

        Setting::set(
            'currency_symbol',
            'EGP'
        );

        $invoice = $this->createSalesInvoice();

        $response = $this->actingAs($this->admin)->get(
            route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertOk();

        $response->assertSee(
            'Heba Trading Company'
        );

        $response->assertSee(
            '01000000000'
        );

        $response->assertSee(
            'EGP'
        );
    }

    public function test_printed_invoice_shows_totals(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this->actingAs($this->admin)->get(
            route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertOk();

        $response->assertSee(
            'Subtotal'
        );

        $response->assertSee(
            'Discount'
        );

        $response->assertSee(
            'Tax'
        );

        $response->assertSee(
            'Total'
        );

        $response->assertSee(
            'Paid'
        );

        $response->assertSee(
            'Remaining'
        );
    }

    public function test_invoice_page_shows_print_link(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this->actingAs($this->admin)->get(
            route('invoices.show', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );

        $response->assertOk();

        $response->assertSee(
            'Print Invoice'
        );

        $response->assertSee(
            route('invoices.print', [
                'type' => InvoiceType::Sale->value,
                'invoice' => $invoice,
            ])
        );
    }

    private function createSalesInvoice(): Invoice
    {
        return $this->invoiceService->create(
            InvoiceType::Sale,
            $this->manager,
            [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]
        );
    }

    private function createPurchaseInvoice(): Invoice
    {
        return $this->invoiceService->create(
            InvoiceType::Purchase,
            $this->manager,
            [
                'supplier_id' => $this->supplier->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]
        );
    }
}
