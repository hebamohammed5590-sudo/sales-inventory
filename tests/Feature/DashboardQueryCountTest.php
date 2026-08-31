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
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DashboardQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $cashier;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'cache.default',
            'database'
        );

        Cache::flush();

        Notification::fake();

        $this->manager = User::factory()->create([
            'role' => Role::Manager,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->cashier = User::factory()->create([
            'role' => Role::Cashier,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->customer = Customer::factory()->create();

        $this->supplier = Supplier::factory()->create();

        Setting::set(
            'tax_rate',
            '0'
        );

        Setting::set(
            'currency_symbol',
            'EGP'
        );

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 100,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->invoiceService = app(
            InvoiceService::class
        );
    }

    public function test_dashboard_executes_fewer_than_twenty_queries_with_cold_cache(): void
    {
        $this->createConfirmedInvoice(
            InvoiceType::Sale
        );

        $this->createConfirmedInvoice(
            InvoiceType::Purchase
        );

        Cache::flush();

        $queries = [];

        DB::listen(
            function (QueryExecuted $query) use (&$queries): void {
                $queries[] = $query->sql;
            }
        );

        $response = $this
            ->actingAs(
                $this->manager
            )
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $this->assertLessThan(
            20,

            count(
                $queries
            ),

            sprintf(
                "Dashboard executed %d queries; expected fewer than 20.\n\n%s",

                count(
                    $queries
                ),

                implode(
                    "\n",

                    $queries
                )
            )
        );
    }

    public function test_dashboard_does_not_execute_one_query_per_invoice(): void
    {
        for ($index = 0; $index < 10; $index++) {
            $this->createConfirmedInvoice(
                InvoiceType::Sale
            );
        }

        Cache::flush();

        $queries = [];

        DB::listen(
            function (QueryExecuted $query) use (&$queries): void {
                $queries[] = $query->sql;
            }
        );

        $response = $this
            ->actingAs(
                $this->manager
            )
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $this->assertLessThan(
            20,
            count($queries),
            sprintf(
                "Dashboard with 10 invoices executed %d queries; expected fewer than 20.\n\n%s",
                count($queries),
                implode("\n", $queries)
            )
        );
    }

    public function test_cashier_dashboard_executes_fewer_than_twenty_queries(): void
    {
        $this->createConfirmedInvoice(
            InvoiceType::Sale
        );

        $this->createConfirmedInvoice(
            InvoiceType::Purchase
        );

        Cache::flush();

        $queries = [];

        DB::listen(
            function (QueryExecuted $query) use (&$queries): void {
                $queries[] = $query->sql;
            }
        );

        $response = $this
            ->actingAs(
                $this->cashier
            )
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $this->assertLessThan(
            20,

            count(
                $queries
            ),

            sprintf(
                "Cashier dashboard executed %d queries; expected fewer than 20.\n\n%s",

                count(
                    $queries
                ),

                implode(
                    "\n",

                    $queries
                )
            )
        );
    }

    private function createConfirmedInvoice(
        InvoiceType $type
    ): Invoice {
        $data = [
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                ],
            ],
        ];

        if ($type === InvoiceType::Sale) {
            $data['customer_id'] = $this->customer->id;
        } else {
            $data['supplier_id'] = $this->supplier->id;
        }

        $invoice = $this->invoiceService->create(
            $type,
            $this->manager,
            $data
        );

        return $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }
}
