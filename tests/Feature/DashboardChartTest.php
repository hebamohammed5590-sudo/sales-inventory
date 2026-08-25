<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardChartService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardChartTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

    private Customer $customer;

    private Supplier $supplier;

    private Category $category;

    private Product $product;

    private InvoiceService $invoiceService;

    private DashboardChartService $chartService;

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

        $this->customer = Customer::factory()->create();

        $this->supplier = Supplier::factory()->create();

        $this->category = Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Test Laptop',
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 20,
            'is_active' => true,
        ]);

        $this->invoiceService = app(
            InvoiceService::class
        );

        $this->chartService = app(
            DashboardChartService::class
        );
    }

    public function test_dashboard_contains_four_chart_elements(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('dashboard')
        );

        $response->assertOk();

        $response->assertSee(
            'monthly-sales-chart',
            false
        );

        $response->assertSee(
            'top-products-chart',
            false
        );

        $response->assertSee(
            'sales-by-category-chart',
            false
        );

        $response->assertSee(
            'sales-vs-purchases-chart',
            false
        );
    }

    public function test_dashboard_receives_all_chart_datasets(): void
    {
        $response = $this->actingAs($this->manager)->get(
            route('dashboard')
        );

        $response->assertOk();

        $response->assertViewHas(
            'charts',
            function (array $charts): bool {
                return isset(
                    $charts['monthly_sales'],
                    $charts['top_products'],
                    $charts['sales_by_category'],
                    $charts['sales_vs_purchases']
                );
            }
        );
    }

    public function test_monthly_sales_chart_contains_twelve_months(): void
    {
        $chart = $this->chartService->monthlySales();

        $this->assertCount(
            12,
            $chart['labels']
        );

        $this->assertCount(
            12,
            $chart['values']
        );

        $this->assertSame(
            now()->format('M Y'),
            $chart['labels'][11]
        );
    }

    public function test_monthly_sales_chart_includes_confirmed_sales(): void
    {
        $this->createConfirmedSale(3);

        $chart = $this->chartService->monthlySales();

        $this->assertSame(
            300.0,
            $chart['values'][11]
        );
    }

    public function test_monthly_sales_chart_excludes_draft_sales(): void
    {
        $this->createDraftSale(3);

        $chart = $this->chartService->monthlySales();

        $this->assertSame(
            0.0,
            $chart['values'][11]
        );
    }

    public function test_monthly_sales_chart_excludes_purchase_invoices(): void
    {
        $this->createConfirmedPurchase(4);

        $chart = $this->chartService->monthlySales();

        $this->assertSame(
            0.0,
            $chart['values'][11]
        );
    }

    public function test_top_products_chart_shows_sold_product(): void
    {
        $this->createConfirmedSale(3);

        $chart = $this->chartService->topProducts();

        $this->assertContains(
            'Test Laptop',
            $chart['labels']
        );

        $this->assertContains(
            3,
            $chart['values']
        );
    }

    public function test_top_products_chart_excludes_draft_invoice_items(): void
    {
        $this->createDraftSale(2);

        $chart = $this->chartService->topProducts();

        $this->assertSame(
            [],
            $chart['labels']
        );

        $this->assertSame(
            [],
            $chart['values']
        );
    }

    public function test_sales_by_category_chart_groups_product_sales(): void
    {
        $this->createConfirmedSale(2);

        $chart = $this->chartService->salesByCategory();

        $this->assertContains(
            'Electronics',
            $chart['labels']
        );

        $categoryIndex = array_search(
            'Electronics',
            $chart['labels'],
            true
        );

        $this->assertNotFalse(
            $categoryIndex
        );

        $this->assertSame(
            200.0,
            $chart['values'][$categoryIndex]
        );
    }

    public function test_sales_vs_purchases_chart_contains_six_months(): void
    {
        $chart = $this->chartService->salesVsPurchases(
            $this->manager
        );

        $this->assertCount(
            6,
            $chart['labels']
        );

        $this->assertCount(
            6,
            $chart['sales']
        );

        $this->assertCount(
            6,
            $chart['purchases']
        );
    }

    public function test_manager_can_see_purchase_chart_data(): void
    {
        $this->createConfirmedPurchase(4);

        $chart = $this->chartService->salesVsPurchases(
            $this->manager
        );

        $this->assertTrue(
            $chart['show_purchases']
        );

        $this->assertSame(
            200.0,
            $chart['purchases'][5]
        );
    }

    public function test_admin_can_see_purchase_chart_data(): void
    {
        $this->createConfirmedPurchase(2);

        $chart = $this->chartService->salesVsPurchases(
            $this->admin
        );

        $this->assertTrue(
            $chart['show_purchases']
        );

        $this->assertSame(
            100.0,
            $chart['purchases'][5]
        );
    }

    public function test_cashier_cannot_see_purchase_chart_data(): void
    {
        $this->createConfirmedPurchase(4);

        $chart = $this->chartService->salesVsPurchases(
            $this->cashier
        );

        $this->assertFalse(
            $chart['show_purchases']
        );

        $this->assertSame(
            [],
            $chart['purchases']
        );
    }

    public function test_cashier_dashboard_does_not_receive_purchase_values(): void
    {
        $this->createConfirmedPurchase(4);

        $response = $this->actingAs($this->cashier)->get(
            route('dashboard')
        );

        $response->assertOk();

        $response->assertViewHas(
            'charts',
            function (array $charts): bool {
                return $charts['sales_vs_purchases']['show_purchases'] === false
                    && $charts['sales_vs_purchases']['purchases'] === [];
            }
        );
    }

    public function test_sales_comparison_chart_includes_confirmed_sales(): void
    {
        $this->createConfirmedSale(2);

        $chart = $this->chartService->salesVsPurchases(
            $this->manager
        );

        $this->assertSame(
            200.0,
            $chart['sales'][5]
        );
    }

    private function createDraftSale(
        int $quantity
    ): Invoice {
        return $this->invoiceService->create(
            InvoiceType::Sale,
            $this->manager,
            [
                'customer_id' => $this->customer->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => $quantity,
                    ],
                ],
            ]
        );
    }

    private function createConfirmedSale(
        int $quantity
    ): Invoice {
        $invoice = $this->createDraftSale(
            $quantity
        );

        return $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }

    private function createConfirmedPurchase(
        int $quantity
    ): Invoice {
        $invoice = $this->invoiceService->create(
            InvoiceType::Purchase,
            $this->manager,
            [
                'supplier_id' => $this->supplier->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => $quantity,
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
