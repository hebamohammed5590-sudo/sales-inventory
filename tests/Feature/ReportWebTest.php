<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportWebTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    private User $admin;

    private User $manager;

    private User $cashier;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = app(
            InvoiceService::class
        );

        Setting::set(
            'tax_rate',
            '0'
        );

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create();

        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_reports_page(): void
    {
        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('reports.index')
            );

        $response->assertOk();

        $response->assertSeeText(
            'Sales Report'
        );

        $response->assertSeeText(
            'Purchases Report'
        );
    }

    public function test_manager_can_view_reports_page(): void
    {
        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.index')
            );

        $response->assertOk();
    }

    public function test_cashier_cannot_view_reports_page(): void
    {
        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('reports.index')
            );

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_reports_page(): void
    {
        $response = $this->get(
            route('reports.index')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_view_sales_report(): void
    {
        $invoice = $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('reports.sales')
            );

        $response->assertOk();

        $response->assertSeeText(
            'Sales Report'
        );

        $response->assertSeeText(
            $invoice->invoice_number
        );

        $response->assertSeeText(
            '200.00'
        );
    }

    public function test_manager_can_view_purchases_report(): void
    {
        $invoice = $this->createConfirmedPurchase();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.purchases')
            );

        $response->assertOk();

        $response->assertSeeText(
            'Purchases Report'
        );

        $response->assertSeeText(
            $invoice->invoice_number
        );

        $response->assertSeeText(
            '150.00'
        );
    }

    public function test_cashier_cannot_view_sales_report(): void
    {
        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('reports.sales')
            );

        $response->assertForbidden();
    }

    public function test_cashier_cannot_view_purchases_report(): void
    {
        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('reports.purchases')
            );

        $response->assertForbidden();
    }

    public function test_sales_report_accepts_valid_date_range(): void
    {
        $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.sales', [
                    'from' => today()
                        ->subDays(7)
                        ->toDateString(),

                    'to' => today()
                        ->toDateString(),
                ])
            );

        $response->assertOk();

        $response->assertSeeText(
            '200.00'
        );
    }

    public function test_sales_report_rejects_invalid_date_range(): void
    {
        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.sales', [
                    'from' => today()
                        ->toDateString(),

                    'to' => today()
                        ->subDay()
                        ->toDateString(),
                ])
            );

        $response->assertSessionHasErrors(
            'to'
        );
    }

    public function test_sales_report_does_not_show_purchase_invoice(): void
    {
        $purchaseInvoice = $this->createConfirmedPurchase();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.sales')
            );

        $response->assertOk();

        $response->assertDontSee(
            $purchaseInvoice->invoice_number
        );
    }

    public function test_purchases_report_does_not_show_sales_invoice(): void
    {
        $salesInvoice = $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.purchases')
            );

        $response->assertOk();

        $response->assertDontSee(
            $salesInvoice->invoice_number
        );
    }

    public function test_admin_can_view_profit_report(): void
    {
        $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('reports.profit')
            );

        $response->assertOk();

        $response->assertSeeText(
            'Profit Report'
        );

        $response->assertSeeText(
            'Revenue'
        );

        $response->assertSeeText(
            'Profit by Product'
        );

        $response->assertSeeText(
            '200.00'
        );
    }

    public function test_manager_can_view_stock_report(): void
    {
        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.stock')
            );

        $response->assertOk();

        $response->assertSeeText(
            'Stock Report'
        );

        $response->assertSeeText(
            'Inventory Details'
        );

        $response->assertSeeText(
            $this->product->sku
        );
    }

    public function test_cashier_cannot_view_profit_report(): void
    {
        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('reports.profit')
            );

        $response->assertForbidden();
    }

    public function test_cashier_cannot_view_stock_report(): void
    {
        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('reports.stock')
            );

        $response->assertForbidden();
    }

    public function test_profit_report_accepts_date_range(): void
    {
        $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.profit', [
                    'from' => today()
                        ->subDays(7)
                        ->toDateString(),

                    'to' => today()
                        ->toDateString(),
                ])
            );

        $response->assertOk();

        $response->assertSeeText(
            '200.00'
        );
    }

    public function test_stock_report_shows_low_stock_indicator(): void
    {
        $this->product->forceFill([
            'quantity' => 2,
            'reorder_level' => 5,
        ])->save();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.stock')
            );

        $response->assertOk();

        $response->assertSeeText(
            'Low Stock'
        );
    }

    public function test_admin_can_view_customer_statement(): void
    {
        $invoice = $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('reports.customers.statement', [
                    'customer' => $this->customer,
                ])
            );

        $response->assertOk();

        $response->assertSeeText(
            'Customer Statement'
        );

        $response->assertSeeText(
            $this->customer->name
        );

        $response->assertSeeText(
            $invoice->invoice_number
        );
    }

    public function test_manager_can_view_customer_statement(): void
    {
        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.customers.statement', [
                    'customer' => $this->customer,
                ])
            );

        $response->assertOk();

        $response->assertSeeText(
            'Outstanding Balance'
        );
    }

    public function test_cashier_cannot_view_customer_statement(): void
    {
        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('reports.customers.statement', [
                    'customer' => $this->customer,
                ])
            );

        $response->assertForbidden();
    }

    public function test_customer_statement_accepts_date_range(): void
    {
        $invoice = $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.customers.statement', [
                    'customer' => $this->customer,

                    'from' => today()
                        ->subDays(7)
                        ->toDateString(),

                    'to' => today()
                        ->toDateString(),
                ])
            );

        $response->assertOk();

        $response->assertSeeText(
            $invoice->invoice_number
        );
    }

    public function test_customer_statement_does_not_show_another_customer_invoice(): void
    {
        $invoice = $this->createConfirmedSale();

        $anotherCustomer = Customer::factory()->create();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.customers.statement', [
                    'customer' => $anotherCustomer,
                ])
            );

        $response->assertOk();

        $response->assertDontSee(
            $invoice->invoice_number
        );
    }

    public function test_admin_can_export_sales_report_as_csv(): void
    {
        $invoice = $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('reports.sales.export')
            );

        $response->assertOk();

        $response->assertHeader(
            'Content-Type',
            'text/csv; charset=UTF-8'
        );

        $response->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Invoice Number',
            $content
        );

        $this->assertStringContainsString(
            $invoice->invoice_number,
            $content
        );

        $this->assertStringContainsString(
            '200.00',
            $content
        );
    }

    public function test_manager_can_export_purchases_report_as_csv(): void
    {
        $invoice = $this->createConfirmedPurchase();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.purchases.export')
            );

        $response->assertOk();

        $response->assertDownload();

        $content = $response->streamedContent();

        $this->assertStringContainsString(
            'Supplier',
            $content
        );

        $this->assertStringContainsString(
            $invoice->invoice_number,
            $content
        );

        $this->assertStringContainsString(
            '150.00',
            $content
        );
    }

    public function test_cashier_cannot_export_sales_report(): void
    {
        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('reports.sales.export')
            );

        $response->assertForbidden();
    }

    public function test_cashier_cannot_export_purchases_report(): void
    {
        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('reports.purchases.export')
            );

        $response->assertForbidden();
    }

    public function test_guest_cannot_export_reports(): void
    {
        $response = $this->get(
            route('reports.sales.export')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_sales_csv_export_respects_date_range(): void
    {
        $invoice = $this->createConfirmedSale();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.sales.export', [
                    'from' => today()
                        ->subDays(7)
                        ->toDateString(),

                    'to' => today()
                        ->toDateString(),
                ])
            );

        $response->assertOk();

        $this->assertStringContainsString(
            $invoice->invoice_number,
            $response->streamedContent()
        );
    }

    public function test_sales_csv_does_not_include_purchase_invoices(): void
    {
        $purchaseInvoice = $this->createConfirmedPurchase();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.sales.export')
            );

        $response->assertOk();

        $this->assertStringNotContainsString(
            $purchaseInvoice->invoice_number,
            $response->streamedContent()
        );
    }

    public function test_csv_export_contains_utf8_bom(): void
    {
        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('reports.sales.export')
            );

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $content
        );
    }

    public function test_admin_can_export_profit_report_as_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('reports.profit.export')
        );

        $response->assertOk();

        $response->assertHeader(
            'content-type',
            'text/csv; charset=UTF-8'
        );

        $this->assertNotEmpty(
            $response->streamedContent()
        );
    }

    public function test_manager_can_export_stock_report_as_csv(): void
    {
        $response = $this->actingAs($this->manager)->get(
            route('reports.stock.export')
        );

        $response->assertOk();

        $response->assertHeader(
            'content-type',
            'text/csv; charset=UTF-8'
        );

        $this->assertNotEmpty(
            $response->streamedContent()
        );
    }

    public function test_admin_can_export_customer_statement_as_csv(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route(
                'reports.customers.statement.export',
                $this->customer
            )
        );

        $response->assertOk();

        $response->assertHeader(
            'content-type',
            'text/csv; charset=UTF-8'
        );

        $this->assertNotEmpty(
            $response->streamedContent()
        );
    }

    public function test_cashier_cannot_export_profit_report(): void
    {
        $response = $this->actingAs($this->cashier)->get(
            route('reports.profit.export')
        );

        $response->assertForbidden();
    }

    public function test_cashier_cannot_export_stock_report(): void
    {
        $response = $this->actingAs($this->cashier)->get(
            route('reports.stock.export')
        );

        $response->assertForbidden();
    }

    public function test_cashier_cannot_export_customer_statement(): void
    {
        $response = $this->actingAs($this->cashier)->get(
            route(
                'reports.customers.statement.export',
                $this->customer
            )
        );

        $response->assertForbidden();
    }

    private function createConfirmedSale()
    {
        $invoice = $this->invoiceService->create(
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

        return $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }

    private function createConfirmedPurchase()
    {
        $invoice = $this->invoiceService->create(
            InvoiceType::Purchase,
            $this->manager,
            [
                'supplier_id' => $this->supplier->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 3,
                    ],
                ],
            ]
        );

        return $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }
}
