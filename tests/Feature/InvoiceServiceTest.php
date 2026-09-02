<?php

namespace Tests\Feature;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\ProductReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $service;

    private User $user;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InvoiceService::class);
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
        ]);
    }

    public function test_can_create_draft_sales_invoice(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
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

        $this->assertSame(InvoiceStatus::Draft, $invoice->status);
        $this->assertSame('200.00', (string) $invoice->subtotal);
        $this->assertSame('200.00', (string) $invoice->total);
        $this->assertCount(1, $invoice->items);
        $this->assertSame(10, $this->product->fresh()->quantity);
    }

    public function test_can_create_invoice_with_fixed_discount_and_tax(): void
    {
        Setting::set('tax_rate', '14.00');

        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,
                'discount_type' => DiscountType::Fixed,
                'discount_value' => '20.00',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]
        );

        // Subtotal = 200.00
        // Discount = 20.00
        // Taxable = 180.00
        // Tax (14%) = 25.20
        // Total = 205.20
        $this->assertSame('200.00', (string) $invoice->subtotal);
        $this->assertSame('20.00', (string) $invoice->discount);
        $this->assertSame('25.20', (string) $invoice->tax);
        $this->assertSame('205.20', (string) $invoice->total);
    }

    public function test_can_create_invoice_with_percentage_discount(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,
                'discount_type' => DiscountType::Percentage,
                'discount_value' => '10.00',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                    ],
                ],
            ]
        );

        // Subtotal = 200.00
        // Discount (10%) = 20.00
        // Total = 180.00
        $this->assertSame('200.00', (string) $invoice->subtotal);
        $this->assertSame('20.00', (string) $invoice->discount);
        $this->assertSame('180.00', (string) $invoice->total);
    }

    public function test_confirming_sales_invoice_decreases_stock(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 3,
                    ],
                ],
            ]
        );

        $confirmedInvoice = $this->service->confirm($invoice, $this->user);

        $this->assertSame(InvoiceStatus::Confirmed, $confirmedInvoice->status);
        $this->assertNotNull($confirmedInvoice->confirmed_at);
        $this->assertSame(7, $this->product->fresh()->quantity);
    }

    public function test_confirming_purchase_invoice_increases_stock(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Purchase,
            $this->user,
            [
                'supplier_id' => $this->supplier->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                    ],
                ],
            ]
        );

        $confirmedInvoice = $this->service->confirm($invoice, $this->user);

        $this->assertSame(InvoiceStatus::Confirmed, $confirmedInvoice->status);
        $this->assertSame(15, $this->product->fresh()->quantity);
    }

    public function test_cancelling_confirmed_sales_invoice_restores_stock(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 4,
                    ],
                ],
            ]
        );

        $confirmedInvoice = $this->service->confirm($invoice, $this->user);
        $this->assertSame(6, $this->product->fresh()->quantity);

        $cancelledInvoice = $this->service->cancel($confirmedInvoice, $this->user);

        $this->assertSame(InvoiceStatus::Cancelled, $cancelledInvoice->status);
        $this->assertNotNull($cancelledInvoice->cancelled_at);
        $this->assertSame(10, $this->product->fresh()->quantity);
    }

    public function test_sales_invoice_with_product_return_cannot_be_cancelled(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 4,
                    ],
                ],
            ]
        );

        $confirmedInvoice = $this->service->confirm(
            $invoice,
            $this->user
        );

        $this->assertSame(
            6,
            $this->product->fresh()->quantity
        );

        $confirmedInvoice->load('items');

        $invoiceItem = $confirmedInvoice->items->first();

        $returnService = app(
            ProductReturnService::class
        );

        $productReturn = $returnService->create(
            $confirmedInvoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 1,
                ],
                'reason' => 'Customer returned one unit.',
            ]
        );

        $this->assertNotNull(
            $productReturn->id
        );

        $this->assertSame(
            7,
            $this->product->fresh()->quantity
        );

        try {
            $this->service->cancel(
                $confirmedInvoice->fresh(),
                $this->user
            );

            $this->fail(
                'Expected cancellation to fail because the sales invoice already has a product return.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'invoice',
                $exception->errors()
            );
        }

        $this->assertSame(
            7,
            $this->product->fresh()->quantity
        );

        $this->assertSame(
            InvoiceStatus::Confirmed,
            $confirmedInvoice->fresh()->status
        );
    }

    public function test_cannot_cancel_purchase_invoice_if_stock_becomes_negative(): void
    {
        $purchaseInvoice = $this->service->create(
            InvoiceType::Purchase,
            $this->user,
            [
                'supplier_id' => $this->supplier->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                    ],
                ],
            ]
        );

        $confirmedPurchase = $this->service->confirm(
            $purchaseInvoice,
            $this->user
        );

        $this->assertSame(
            15,
            $this->product->fresh()->quantity
        );

        $salesInvoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 13,
                    ],
                ],
            ]
        );

        $this->service->confirm(
            $salesInvoice,
            $this->user
        );

        $this->assertSame(
            2,
            $this->product->fresh()->quantity
        );

        try {
            $this->service->cancel(
                $confirmedPurchase,
                $this->user
            );

            $this->fail(
                'Expected cancellation to fail because stock would become negative.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'stock',
                $exception->errors()
            );
        }

        $this->assertSame(
            2,
            $this->product->fresh()->quantity
        );

        $this->assertSame(
            InvoiceStatus::Confirmed,
            $confirmedPurchase->fresh()->status
        );
    }

    public function test_sales_invoice_can_override_product_unit_price(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,

                        'quantity' => 2,

                        'unit_price' => '80.50',
                    ],
                ],
            ]
        );

        $item = $invoice->items->first();

        $this->assertNotNull($item);

        $this->assertSame(
            '80.50',
            (string) $item->unit_price
        );

        $this->assertSame(
            '161.00',
            (string) $item->line_total
        );

        $this->assertSame(
            '161.00',
            (string) $invoice->subtotal
        );

        $this->assertSame(
            '161.00',
            (string) $invoice->total
        );
    }

    public function test_purchase_invoice_can_override_product_unit_price(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Purchase,
            $this->user,
            [
                'supplier_id' => $this->supplier->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,

                        'quantity' => 3,

                        'unit_price' => '45.25',
                    ],
                ],
            ]
        );

        $item = $invoice->items->first();

        $this->assertNotNull($item);

        $this->assertSame(
            '45.25',
            (string) $item->unit_price
        );

        $this->assertSame(
            '135.75',
            (string) $item->line_total
        );

        $this->assertSame(
            '135.75',
            (string) $invoice->subtotal
        );

        $this->assertSame(
            '135.75',
            (string) $invoice->total
        );
    }

    public function test_invoice_uses_product_price_when_unit_price_is_not_provided(): void
    {
        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
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

        $item = $invoice->items->first();

        $this->assertNotNull($item);

        $this->assertSame(
            '100.00',
            (string) $item->unit_price
        );

        $this->assertSame(
            '200.00',
            (string) $invoice->subtotal
        );
    }

    public function test_overridden_unit_price_is_used_with_discount_and_tax(): void
    {
        Setting::set(
            'tax_rate',
            '14.00'
        );

        $invoice = $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,

                'discount_type' => DiscountType::Fixed,

                'discount_value' => '10.00',

                'items' => [
                    [
                        'product_id' => $this->product->id,

                        'quantity' => 2,

                        'unit_price' => '80.00',
                    ],
                ],
            ]
        );

        $this->assertSame(
            '160.00',
            (string) $invoice->subtotal
        );

        $this->assertSame(
            '10.00',
            (string) $invoice->discount
        );

        $this->assertSame(
            '21.00',
            (string) $invoice->tax
        );

        $this->assertSame(
            '171.00',
            (string) $invoice->total
        );
    }

    public function test_invoice_rejects_zero_unit_price(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->service->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,

                        'quantity' => 1,

                        'unit_price' => '0.00',
                    ],
                ],
            ]
        );
    }
}
