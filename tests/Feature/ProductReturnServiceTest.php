<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\StockMovementType;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\ProductReturnService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductReturnServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductReturnService $service;

    private InvoiceService $invoiceService;

    private User $user;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProductReturnService::class);
        $this->invoiceService = app(InvoiceService::class);

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
        ]);
    }

    public function test_partial_return_restores_stock(): void
    {
        $invoice = $this->createConfirmedSale(4);

        $this->assertSame(
            6,
            $this->product->fresh()->quantity
        );

        $invoiceItem = $invoice->items->first();

        $productReturn = $this->service->create(
            $invoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 2,
                ],
                'reason' => 'Customer returned two units.',
            ]
        );

        $this->assertSame(
            8,
            $this->product->fresh()->quantity
        );

        $this->assertCount(
            1,
            $productReturn->items
        );

        $this->assertSame(
            2,
            $productReturn->items->first()->quantity
        );

        $this->assertSame(
            '200.00',
            (string) $productReturn->subtotal
        );
    }

    public function test_return_number_is_generated(): void
    {
        $invoice = $this->createConfirmedSale(2);

        $invoiceItem = $invoice->items->first();

        $productReturn = $this->service->create(
            $invoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 1,
                ],
            ]
        );

        $expectedNumber = sprintf(
            'RET-%s-%06d',
            now()->format('Y'),
            $productReturn->id
        );

        $this->assertSame(
            $expectedNumber,
            $productReturn->return_number
        );

        $this->assertMatchesRegularExpression(
            '/^RET-\d{4}-\d{6}$/',
            $productReturn->return_number
        );
    }

    public function test_return_uses_original_invoice_item_price(): void
    {
        $invoice = $this->invoiceService->create(
            InvoiceType::Sale,
            $this->user,
            [
                'customer_id' => $this->customer->id,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 3,
                        'unit_price' => '75.50',
                    ],
                ],
            ]
        );

        $invoice = $this->invoiceService->confirm(
            $invoice,
            $this->user
        );

        $invoice->load('items.product');

        $invoiceItem = $invoice->items->first();

        $productReturn = $this->service->create(
            $invoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 2,
                ],
            ]
        );

        $returnItem = $productReturn->items->first();

        $this->assertSame(
            '75.50',
            (string) $returnItem->unit_price
        );

        $this->assertSame(
            '151.00',
            (string) $returnItem->line_total
        );

        $this->assertSame(
            '151.00',
            (string) $productReturn->subtotal
        );
    }

    public function test_multiple_partial_returns_are_allowed(): void
    {
        $invoice = $this->createConfirmedSale(5);

        $invoiceItem = $invoice->items->first();

        $firstReturn = $this->service->create(
            $invoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 2,
                ],
            ]
        );

        $this->assertSame(
            7,
            $this->product->fresh()->quantity
        );

        $secondReturn = $this->service->create(
            $invoice->fresh(),
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 2,
                ],
            ]
        );

        $this->assertSame(
            9,
            $this->product->fresh()->quantity
        );

        $this->assertNotSame(
            $firstReturn->id,
            $secondReturn->id
        );

        $this->assertSame(
            4,
            (int) $invoiceItem->productReturnItems()
                ->sum('quantity')
        );
    }

    public function test_cannot_return_more_than_remaining_quantity(): void
    {
        $invoice = $this->createConfirmedSale(4);

        $invoiceItem = $invoice->items->first();

        $this->service->create(
            $invoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 3,
                ],
            ]
        );

        $this->assertSame(
            9,
            $this->product->fresh()->quantity
        );

        try {
            $this->service->create(
                $invoice->fresh(),
                $this->user,
                [
                    'items' => [
                        $invoiceItem->id => 2,
                    ],
                ]
            );

            $this->fail(
                'Expected over-return to be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                "items.{$invoiceItem->id}",
                $exception->errors()
            );
        }

        $this->assertSame(
            9,
            $this->product->fresh()->quantity
        );

        $this->assertDatabaseCount(
            'product_returns',
            1
        );
    }

    public function test_fully_returned_item_cannot_be_returned_again(): void
    {
        $invoice = $this->createConfirmedSale(3);

        $invoiceItem = $invoice->items->first();

        $this->service->create(
            $invoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 3,
                ],
            ]
        );

        $this->assertSame(
            10,
            $this->product->fresh()->quantity
        );

        try {
            $this->service->create(
                $invoice->fresh(),
                $this->user,
                [
                    'items' => [
                        $invoiceItem->id => 1,
                    ],
                ]
            );

            $this->fail(
                'Expected another return to be rejected because the item is fully returned.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                "items.{$invoiceItem->id}",
                $exception->errors()
            );
        }

        $this->assertSame(
            10,
            $this->product->fresh()->quantity
        );
    }

    public function test_purchase_invoice_cannot_receive_product_return(): void
    {
        $invoice = $this->invoiceService->create(
            InvoiceType::Purchase,
            $this->user,
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

        $invoice = $this->invoiceService->confirm(
            $invoice,
            $this->user
        );

        $invoice->load('items.product');

        $invoiceItem = $invoice->items->first();

        try {
            $this->service->create(
                $invoice,
                $this->user,
                [
                    'items' => [
                        $invoiceItem->id => 1,
                    ],
                ]
            );

            $this->fail(
                'Expected purchase invoice return to be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'invoice',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'product_returns',
            0
        );
    }

    public function test_draft_sales_invoice_cannot_receive_product_return(): void
    {
        $invoice = $this->invoiceService->create(
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

        $invoice->load('items.product');

        $invoiceItem = $invoice->items->first();

        try {
            $this->service->create(
                $invoice,
                $this->user,
                [
                    'items' => [
                        $invoiceItem->id => 1,
                    ],
                ]
            );

            $this->fail(
                'Expected draft sales invoice return to be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'invoice',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'product_returns',
            0
        );

        $this->assertSame(
            10,
            $this->product->fresh()->quantity
        );
    }

    public function test_cancelled_sales_invoice_cannot_receive_product_return(): void
    {
        $invoice = $this->createConfirmedSale(2);

        $invoiceItem = $invoice->items->first();

        $cancelledInvoice = $this->invoiceService->cancel(
            $invoice,
            $this->user
        );

        $this->assertSame(
            InvoiceStatus::Cancelled,
            $cancelledInvoice->status
        );

        $this->assertSame(
            10,
            $this->product->fresh()->quantity
        );

        try {
            $this->service->create(
                $cancelledInvoice,
                $this->user,
                [
                    'items' => [
                        $invoiceItem->id => 1,
                    ],
                ]
            );

            $this->fail(
                'Expected cancelled sales invoice return to be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'invoice',
                $exception->errors()
            );
        }

        $this->assertSame(
            10,
            $this->product->fresh()->quantity
        );

        $this->assertDatabaseCount(
            'product_returns',
            0
        );
    }

    public function test_return_creates_return_stock_movement_with_product_return_source(): void
    {
        $invoice = $this->createConfirmedSale(3);

        $invoiceItem = $invoice->items->first();

        $productReturn = $this->service->create(
            $invoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 2,
                ],
            ]
        );

        $this->assertCount(
            1,
            $productReturn->stockMovements
        );

        $movement = $productReturn->stockMovements->first();

        $this->assertSame(
            StockMovementType::Return,
            $movement->type
        );

        $this->assertSame(
            2,
            $movement->quantity_change
        );

        $this->assertSame(
            7,
            $movement->quantity_before
        );

        $this->assertSame(
            9,
            $movement->quantity_after
        );

        $this->assertInstanceOf(
            ProductReturn::class,
            $movement->source
        );

        $this->assertSame(
            $productReturn->id,
            $movement->source->id
        );
    }

    public function test_product_return_is_recorded_in_activity_log(): void
    {
        $invoice = $this->createConfirmedSale(2);

        $invoiceItem = $invoice->items->first();

        $productReturn = $this->service->create(
            $invoice,
            $this->user,
            [
                'items' => [
                    $invoiceItem->id => 1,
                ],
                'reason' => 'Damaged product.',
            ]
        );

        $activity = ActivityLog::query()
            ->where(
                'action',
                'product_return.created'
            )
            ->latest('id')
            ->first();

        $this->assertNotNull(
            $activity
        );

        $this->assertInstanceOf(
            ProductReturn::class,
            $activity->subject
        );

        $this->assertSame(
            $productReturn->id,
            $activity->subject->id
        );

        $this->assertSame(
            $this->user->id,
            $activity->actor_id
        );

        $this->assertSame(
            $productReturn->return_number,
            $activity->properties['return_number']
        );

        $this->assertSame(
            $invoice->invoice_number,
            $activity->properties['invoice_number']
        );
    }

    public function test_return_requires_at_least_one_positive_quantity(): void
    {
        $invoice = $this->createConfirmedSale(2);

        $invoiceItem = $invoice->items->first();

        try {
            $this->service->create(
                $invoice,
                $this->user,
                [
                    'items' => [
                        $invoiceItem->id => 0,
                    ],
                ]
            );

            $this->fail(
                'Expected return without a positive quantity to be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'items',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'product_returns',
            0
        );

        $this->assertSame(
            8,
            $this->product->fresh()->quantity
        );
    }

    public function test_empty_return_items_are_rejected(): void
    {
        $invoice = $this->createConfirmedSale(2);

        try {
            $this->service->create(
                $invoice,
                $this->user,
                [
                    'items' => [],
                ]
            );

            $this->fail(
                'Expected an empty product return to be rejected.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'items',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'product_returns',
            0
        );

        $this->assertSame(
            8,
            $this->product->fresh()->quantity
        );
    }

    private function createConfirmedSale(
        int $quantity
    ): Invoice {
        $invoice = $this->invoiceService->create(
            InvoiceType::Sale,
            $this->user,
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

        $invoice = $this->invoiceService->confirm(
            $invoice,
            $this->user
        );

        return $invoice->load(
            'items.product'
        );
    }
}
