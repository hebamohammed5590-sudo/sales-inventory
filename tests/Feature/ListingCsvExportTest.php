<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ListingCsvExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

    private Category $electronics;

    private Category $furniture;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

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

        $this->electronics = Category::factory()->create([
            'name' => 'Electronics',
            'is_active' => true,
        ]);

        $this->furniture = Category::factory()->create([
            'name' => 'Furniture',
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create([
            'name' => 'Ahmed Customer',
            'phone' => '01000000111',
        ]);

        $this->supplier = Supplier::factory()->create([
            'name' => 'Main Supplier',
            'phone' => '01000000222',
        ]);

        Setting::set(
            'tax_rate',
            '0'
        );

        $this->product = Product::factory()->create([
            'category_id' => $this->electronics->id,
            'name' => 'Laptop Computer',
            'sku' => 'PRD-LAPTOP-001',
            'cost_price' => '100.00',
            'sell_price' => '150.00',
            'quantity' => 50,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->invoiceService = app(
            InvoiceService::class
        );
    }

    public function test_admin_can_export_products(): void
    {
        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route('products.export')
            );

        $response->assertOk();

        $response->assertDownload(
            'products.csv'
        );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Laptop Computer',
            $content
        );

        $this->assertStringContainsString(
            'PRD-LAPTOP-001',
            $content
        );

        $this->assertStringContainsString(
            'Electronics',
            $content
        );
    }

    public function test_manager_can_export_products(): void
    {
        $response = $this
            ->actingAs(
                $this->manager
            )
            ->get(
                route('products.export')
            );

        $response->assertOk();

        $response->assertDownload(
            'products.csv'
        );
    }

    public function test_guest_cannot_export_products(): void
    {
        $response = $this->get(
            route('products.export')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_products_export_respects_search_filter(): void
    {
        Product::factory()->create([
            'category_id' => $this->furniture->id,
            'name' => 'Wooden Chair',
            'sku' => 'PRD-CHAIR-001',
            'quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'products.export',
                    [
                        'search' => 'Laptop',
                    ]
                )
            );

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Laptop Computer',
            $content
        );

        $this->assertStringNotContainsString(
            'Wooden Chair',
            $content
        );
    }

    public function test_products_export_can_search_by_sku(): void
    {
        Product::factory()->create([
            'category_id' => $this->furniture->id,
            'name' => 'Wooden Chair',
            'sku' => 'PRD-CHAIR-001',
            'quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'products.export',
                    [
                        'search' => 'LAPTOP-001',
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'PRD-LAPTOP-001',
            $content
        );

        $this->assertStringNotContainsString(
            'PRD-CHAIR-001',
            $content
        );
    }

    public function test_products_export_respects_category_filter(): void
    {
        Product::factory()->create([
            'category_id' => $this->furniture->id,
            'name' => 'Wooden Chair',
            'sku' => 'PRD-CHAIR-001',
            'quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'products.export',
                    [
                        'category_id' => $this->electronics->id,
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Laptop Computer',
            $content
        );

        $this->assertStringNotContainsString(
            'Wooden Chair',
            $content
        );
    }

    public function test_products_export_respects_status_filter(): void
    {
        Product::factory()->create([
            'category_id' => $this->electronics->id,
            'name' => 'Inactive Keyboard',
            'sku' => 'PRD-KEYBOARD-001',
            'quantity' => 10,
            'is_active' => false,
        ]);

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'products.export',
                    [
                        'status' => 'active',
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Laptop Computer',
            $content
        );

        $this->assertStringNotContainsString(
            'Inactive Keyboard',
            $content
        );
    }

    public function test_products_export_respects_sort_direction(): void
    {
        Product::factory()->create([
            'category_id' => $this->electronics->id,
            'name' => 'Alpha Mouse',
            'sku' => 'PRD-MOUSE-001',
            'quantity' => 10,
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'products.export',
                    [
                        'sort' => 'name',
                        'direction' => 'asc',
                    ]
                )
            );

        $content = $response->streamedContent();

        $mousePosition = strpos(
            $content,
            'Alpha Mouse'
        );

        $laptopPosition = strpos(
            $content,
            'Laptop Computer'
        );

        $this->assertNotFalse(
            $mousePosition
        );

        $this->assertNotFalse(
            $laptopPosition
        );

        $this->assertLessThan(
            $laptopPosition,
            $mousePosition
        );
    }

    public function test_admin_can_export_customers(): void
    {
        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route('customers.export')
            );

        $response->assertOk();

        $response->assertDownload(
            'customers.csv'
        );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Ahmed Customer',
            $content
        );

        $this->assertStringContainsString(
            '01000000111',
            $content
        );
    }

    public function test_cashier_can_export_customers_when_authorized_to_view_them(): void
    {
        $response = $this
            ->actingAs(
                $this->cashier
            )
            ->get(
                route('customers.export')
            );

        $response->assertOk();

        $response->assertDownload(
            'customers.csv'
        );
    }

    public function test_customers_export_respects_search_filter(): void
    {
        Customer::factory()->create([
            'name' => 'Sara Different',
            'phone' => '01000000333',
        ]);

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'customers.export',
                    [
                        'search' => 'Ahmed',
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Ahmed Customer',
            $content
        );

        $this->assertStringNotContainsString(
            'Sara Different',
            $content
        );
    }

    public function test_guest_cannot_export_customers(): void
    {
        $response = $this->get(
            route('customers.export')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_export_suppliers(): void
    {
        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route('suppliers.export')
            );

        $response->assertOk();

        $response->assertDownload(
            'suppliers.csv'
        );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Main Supplier',
            $content
        );

        $this->assertStringContainsString(
            '01000000222',
            $content
        );
    }

    public function test_manager_can_export_suppliers(): void
    {
        $response = $this
            ->actingAs(
                $this->manager
            )
            ->get(
                route('suppliers.export')
            );

        $response->assertOk();

        $response->assertDownload(
            'suppliers.csv'
        );
    }

    public function test_cashier_cannot_export_suppliers(): void
    {
        $response = $this
            ->actingAs(
                $this->cashier
            )
            ->get(
                route('suppliers.export')
            );

        $response->assertForbidden();
    }

    public function test_suppliers_export_respects_search_filter(): void
    {
        Supplier::factory()->create([
            'name' => 'Different Supplier',
            'phone' => '01000000444',
        ]);

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'suppliers.export',
                    [
                        'search' => 'Main',
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Main Supplier',
            $content
        );

        $this->assertStringNotContainsString(
            'Different Supplier',
            $content
        );
    }

    public function test_guest_cannot_export_suppliers(): void
    {
        $response = $this->get(
            route('suppliers.export')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_export_sales_invoices(): void
    {
        $invoice = $this->createInvoice(
            InvoiceType::Sale
        );

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'invoices.export',
                    [
                        'type' => 'sale',
                    ]
                )
            );

        $response->assertOk();

        $response->assertDownload(
            'sales-invoices.csv'
        );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            $invoice->invoice_number,
            $content
        );

        $this->assertStringContainsString(
            'Ahmed Customer',
            $content
        );
    }

    public function test_manager_can_export_purchase_invoices(): void
    {
        $invoice = $this->createInvoice(
            InvoiceType::Purchase
        );

        $response = $this
            ->actingAs(
                $this->manager
            )
            ->get(
                route(
                    'invoices.export',
                    [
                        'type' => 'purchase',
                    ]
                )
            );

        $response->assertOk();

        $response->assertDownload(
            'purchase-invoices.csv'
        );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            $invoice->invoice_number,
            $content
        );

        $this->assertStringContainsString(
            'Main Supplier',
            $content
        );
    }

    public function test_cashier_can_export_sales_invoices(): void
    {
        $this->createInvoice(
            InvoiceType::Sale
        );

        $response = $this
            ->actingAs(
                $this->cashier
            )
            ->get(
                route(
                    'invoices.export',
                    [
                        'type' => 'sale',
                    ]
                )
            );

        $response->assertOk();

        $response->assertDownload(
            'sales-invoices.csv'
        );
    }

    public function test_cashier_cannot_export_purchase_invoices(): void
    {
        $response = $this
            ->actingAs(
                $this->cashier
            )
            ->get(
                route(
                    'invoices.export',
                    [
                        'type' => 'purchase',
                    ]
                )
            );

        $response->assertForbidden();
    }

    public function test_guest_cannot_export_invoices(): void
    {
        $response = $this->get(
            route(
                'invoices.export',
                [
                    'type' => 'sale',
                ]
            )
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_sales_invoice_export_does_not_include_purchase_invoices(): void
    {
        $saleInvoice = $this->createInvoice(
            InvoiceType::Sale
        );

        $purchaseInvoice = $this->createInvoice(
            InvoiceType::Purchase
        );

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'invoices.export',
                    [
                        'type' => 'sale',
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            $saleInvoice->invoice_number,
            $content
        );

        $this->assertStringNotContainsString(
            $purchaseInvoice->invoice_number,
            $content
        );
    }

    public function test_purchase_invoice_export_does_not_include_sales_invoices(): void
    {
        $saleInvoice = $this->createInvoice(
            InvoiceType::Sale
        );

        $purchaseInvoice = $this->createInvoice(
            InvoiceType::Purchase
        );

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'invoices.export',
                    [
                        'type' => 'purchase',
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            $purchaseInvoice->invoice_number,
            $content
        );

        $this->assertStringNotContainsString(
            $saleInvoice->invoice_number,
            $content
        );
    }

    public function test_invoice_export_respects_status_filter(): void
    {
        $draftInvoice = $this->createInvoice(
            InvoiceType::Sale
        );

        $confirmedInvoice = $this->createInvoice(
            InvoiceType::Sale
        );

        $confirmedInvoice = $this->invoiceService->confirm(
            $confirmedInvoice,
            $this->manager
        );

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'invoices.export',
                    [
                        'type' => 'sale',
                        'status' => InvoiceStatus::Confirmed->value,
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            $confirmedInvoice->invoice_number,
            $content
        );

        $this->assertStringNotContainsString(
            $draftInvoice->invoice_number,
            $content
        );
    }

    public function test_invoice_export_respects_invoice_number_search(): void
    {
        $firstInvoice = $this->createInvoice(
            InvoiceType::Sale
        );

        $secondInvoice = $this->createInvoice(
            InvoiceType::Sale
        );

        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'invoices.export',
                    [
                        'type' => 'sale',
                        'search' => $firstInvoice->invoice_number,
                    ]
                )
            );

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            $firstInvoice->invoice_number,
            $content
        );

        $this->assertStringNotContainsString(
            $secondInvoice->invoice_number,
            $content
        );
    }

    public function test_csv_exports_include_utf8_bom(): void
    {
        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route('products.export')
            );

        $content = $response->streamedContent();

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $content
        );
    }

    public function test_products_page_shows_export_button(): void
    {
        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route('products.index')
            );

        $response->assertOk();

        $response->assertSee(
            'Export CSV'
        );

        $response->assertSee(
            route('products.export'),
            false
        );
    }

    public function test_customers_page_shows_export_button(): void
    {
        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route('customers.index')
            );

        $response->assertOk();

        $response->assertSee(
            'Export CSV'
        );

        $response->assertSee(
            route('customers.export'),
            false
        );
    }

    public function test_suppliers_page_shows_export_button(): void
    {
        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route('suppliers.index')
            );

        $response->assertOk();

        $response->assertSee(
            'Export CSV'
        );

        $response->assertSee(
            route('suppliers.export'),
            false
        );
    }

    public function test_sales_invoices_page_shows_export_button(): void
    {
        $response = $this
            ->actingAs(
                $this->admin
            )
            ->get(
                route(
                    'invoices.index',
                    [
                        'type' => 'sale',
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee(
            'Export CSV'
        );

        $response->assertSee(
            route(
                'invoices.export',
                [
                    'type' => 'sale',
                ]
            ),
            false
        );
    }

    public function test_purchase_invoices_page_shows_export_button(): void
    {
        $response = $this
            ->actingAs(
                $this->manager
            )
            ->get(
                route(
                    'invoices.index',
                    [
                        'type' => 'purchase',
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee(
            'Export CSV'
        );

        $response->assertSee(
            route(
                'invoices.export',
                [
                    'type' => 'purchase',
                ]
            ),
            false
        );
    }

    private function createInvoice(
        InvoiceType $type
    ): Invoice {
        $data = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                ],
            ],
        ];

        if ($type === InvoiceType::Sale) {
            $data['customer_id'] = $this->customer->id;
        } else {
            $data['supplier_id'] = $this->supplier->id;
        }

        return $this->invoiceService->create(
            $type,
            $this->manager,
            $data
        );
    }
}
