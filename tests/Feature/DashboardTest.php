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
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    private User $manager;

    private User $cashier;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->invoiceService = app(
            InvoiceService::class
        );

        Setting::set(
            'tax_rate',
            '0'
        );

        $this->manager = User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->customer = Customer::factory()->create();

        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
            'reorder_level' => 5,
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSeeText(
            'Sales',
            false
        );

        $response->assertSeeText(
            'Inventory Value',
            false
        );
    }

    public function test_guest_cannot_view_dashboard(): void
    {
        $response = $this->get(
            route('dashboard')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_dashboard_shows_confirmed_sales_amount(): void
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

        $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertViewHas(
            'dashboard',
            function (array $dashboard): bool {
                return $dashboard['today_sales'] === 20000
                    && $dashboard['monthly_sales'] === 20000;
            }
        );
    }

    public function test_draft_invoice_is_not_counted_in_sales(): void
    {
        $this->invoiceService->create(
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

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            );

        $response->assertViewHas(
            'dashboard',
            function (array $dashboard): bool {
                return $dashboard['today_sales'] === 0;
            }
        );
    }

    public function test_dashboard_calculates_inventory_value(): void
    {
        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            );

        $response->assertViewHas(
            'dashboard',
            function (array $dashboard): bool {
                return $dashboard['inventory_value'] === 50000;
            }
        );
    }

    public function test_dashboard_shows_low_stock_products(): void
    {
        $this->product->forceFill([
            'quantity' => 4,
        ])->save();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            );

        $response->assertViewHas(
            'dashboard',
            function (array $dashboard): bool {
                return $dashboard['low_stock_products']
                    ->contains(
                        'id',
                        $this->product->id
                    );
            }
        );

        $response->assertSee(
            $this->product->sku
        );
    }

    public function test_dashboard_counts_customers(): void
    {
        Customer::factory()->count(2)->create();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            );

        $response->assertViewHas(
            'dashboard',
            function (array $dashboard): bool {
                return $dashboard['total_customers'] === 3;
            }
        );
    }

    public function test_dashboard_lists_latest_invoices(): void
    {
        $invoice = $this->invoiceService->create(
            InvoiceType::Sale,
            $this->manager,
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

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            );

        $response->assertSee(
            $invoice->invoice_number
        );
    }

    public function test_cashier_cannot_see_purchase_invoices_on_dashboard(): void
    {
        $invoice = $this->invoiceService->create(
            InvoiceType::Purchase,
            $this->manager,
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

        $response = $this
            ->actingAs($this->cashier)
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertDontSee(
            $invoice->invoice_number
        );
    }

    public function test_dashboard_data_is_cached(): void
    {
        $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $this->assertTrue(
            Cache::has(
                'dashboard.data.manager'
            )
        );
    }

    public function test_invoice_creation_clears_dashboard_cache(): void
    {
        $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $this->assertTrue(
            Cache::has(
                'dashboard.data.manager'
            )
        );

        $this->invoiceService->create(
            InvoiceType::Sale,
            $this->manager,
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

        $this->assertFalse(
            Cache::has(
                'dashboard.data.manager'
            )
        );
    }

    public function test_invoice_confirmation_clears_dashboard_cache(): void
    {
        $invoice = $this->invoiceService->create(
            InvoiceType::Sale,
            $this->manager,
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

        $this
            ->actingAs($this->manager)
            ->get(
                route('dashboard')
            )
            ->assertOk();

        $this->assertTrue(
            Cache::has(
                'dashboard.data.manager'
            )
        );

        $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );

        $this->assertFalse(
            Cache::has(
                'dashboard.data.manager'
            )
        );
    }
}
