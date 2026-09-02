<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductReturnWebTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    private User $manager;

    private User $cashier;

    private Customer $customer;

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

        $this->manager = User::factory()->create([
            'role' => 'manager',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
        ]);
    }

    public function test_manager_can_open_product_return_create_page(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route(
                    'product-returns.create',
                    $invoice
                )
            );

        $response->assertOk();

        $response->assertSee(
            'Create Product Return'
        );

        $response->assertSee(
            $invoice->invoice_number
        );

        $response->assertSee(
            $this->product->name
        );
    }

    public function test_cashier_can_create_product_return(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $invoiceItem = $invoice
            ->items()
            ->firstOrFail();

        $this->assertSame(
            8,
            $this->product->fresh()->quantity
        );

        $response = $this
            ->actingAs($this->cashier)
            ->post(
                route(
                    'product-returns.store',
                    $invoice
                ),
                [
                    'return_date' => now()
                        ->toDateString(),
                    'reason' => 'Customer return',
                    'items' => [
                        $invoiceItem->id => 1,
                    ],
                ]
            );

        $productReturn = ProductReturn::query()
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'product-returns.show',
                $productReturn
            )
        );

        $response->assertSessionHas(
            'success',
            'Product return created successfully.'
        );

        $this->assertDatabaseHas(
            'product_returns',
            [
                'id' => $productReturn->id,
                'invoice_id' => $invoice->id,
                'user_id' => $this->cashier->id,
                'subtotal' => 10000,
            ]
        );

        $this->assertDatabaseHas(
            'product_return_items',
            [
                'product_return_id' => $productReturn->id,
                'invoice_item_id' => $invoiceItem->id,
                'product_id' => $this->product->id,
                'quantity' => 1,
                'unit_price' => 10000,
                'line_total' => 10000,
            ]
        );

        $this->assertSame(
            9,
            $this->product->fresh()->quantity
        );
    }

    public function test_guest_cannot_access_product_returns(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $this
            ->get(
                route('product-returns.index')
            )
            ->assertRedirect(
                route('login')
            );

        $this
            ->get(
                route(
                    'product-returns.create',
                    $invoice
                )
            )
            ->assertRedirect(
                route('login')
            );

        $this->assertDatabaseCount(
            'product_returns',
            0
        );
    }

    public function test_draft_sales_invoice_cannot_open_return_form(): void
    {
        $invoice = $this->createDraftSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route(
                    'product-returns.create',
                    $invoice
                )
            );

        $response->assertNotFound();
    }

    public function test_purchase_invoice_cannot_open_return_form(): void
    {
        $supplier = Supplier::factory()->create();

        $invoice = $this->invoiceService->create(
            InvoiceType::Purchase,
            $this->manager,
            [
                'supplier_id' => $supplier->id,
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
                route(
                    'product-returns.create',
                    $invoice
                )
            );

        $response->assertNotFound();
    }

    public function test_return_date_cannot_be_before_invoice_date(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $invoiceItem = $invoice
            ->items()
            ->firstOrFail();

        $response = $this
            ->actingAs($this->manager)
            ->post(
                route(
                    'product-returns.store',
                    $invoice
                ),
                [
                    'return_date' => $invoice
                        ->invoice_date
                        ->copy()
                        ->subDay()
                        ->toDateString(),
                    'items' => [
                        $invoiceItem->id => 1,
                    ],
                ]
            );

        $response->assertSessionHasErrors(
            'return_date'
        );

        $this->assertDatabaseCount(
            'product_returns',
            0
        );

        $this->assertSame(
            8,
            $this->product->fresh()->quantity
        );
    }

    public function test_invoice_page_hides_cancel_after_product_return(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $invoiceItem = $invoice
            ->items()
            ->firstOrFail();

        $this
            ->actingAs($this->manager)
            ->post(
                route(
                    'product-returns.store',
                    $invoice
                ),
                [
                    'return_date' => now()
                        ->toDateString(),
                    'items' => [
                        $invoiceItem->id => 1,
                    ],
                ]
            )
            ->assertRedirect();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route(
                    'invoices.show',
                    [
                        'type' => 'sale',
                        'invoice' => $invoice,
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee(
            'Previous Product Returns'
        );

        $response->assertDontSee(
            'Cancel Invoice'
        );
    }

    private function createConfirmedSaleInvoice(): Invoice
    {
        $invoice = $this->createDraftSaleInvoice();

        return $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }

    private function createDraftSaleInvoice(): Invoice
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
}
