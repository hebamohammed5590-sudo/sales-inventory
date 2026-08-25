<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebTest extends TestCase
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

        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
        ]);
    }

    public function test_manager_can_record_partial_payment_from_invoice_page(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '50.00',
                    'method' => 'cash',
                ]
            );

        $response->assertRedirect(
            route('invoices.show', [
                'type' => 'sale',
                'invoice' => $invoice,
            ])
        );

        $response->assertSessionHas(
            'success',
            'Payment recorded successfully.'
        );

        $this->assertDatabaseHas('payments', [
            'payable_type' => Invoice::class,
            'payable_id' => $invoice->id,
            'user_id' => $this->manager->id,
            'amount' => 5000,
            'method' => 'cash',
        ]);

        $this->assertSame(
            InvoiceStatus::PartiallyPaid,
            $invoice->fresh()->status
        );

        $this->assertSame(
            '150.00',
            $invoice->fresh()->remainingAmount()
        );
    }

    public function test_full_payment_marks_invoice_as_paid(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '200.00',
                    'method' => 'card',
                ]
            );

        $response->assertRedirect();

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->fresh()->status
        );

        $this->assertSame(
            '0.00',
            $invoice->fresh()->remainingAmount()
        );
    }

    public function test_two_payments_complete_invoice(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '75.00',
                    'method' => 'cash',
                ]
            )
            ->assertRedirect();

        $this->assertSame(
            InvoiceStatus::PartiallyPaid,
            $invoice->fresh()->status
        );

        $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '125.00',
                    'method' => 'bank_transfer',
                    'reference' => 'BANK-001',
                ]
            )
            ->assertRedirect();

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->fresh()->status
        );

        $this->assertDatabaseCount(
            'payments',
            2
        );
    }

    public function test_cashier_can_pay_sales_invoice(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->cashier)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '50.00',
                    'method' => 'cash',
                ]
            );

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'payable_id' => $invoice->id,
            'user_id' => $this->cashier->id,
            'amount' => 5000,
        ]);
    }

    public function test_cashier_cannot_pay_purchase_invoice(): void
    {
        $invoice = $this->createConfirmedPurchaseInvoice();

        $response = $this
            ->actingAs($this->cashier)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'purchase',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '50.00',
                    'method' => 'cash',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount(
            'payments',
            0
        );
    }

    public function test_guest_cannot_record_payment(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this->post(
            route('invoices.payments.store', [
                'type' => 'sale',
                'invoice' => $invoice,
            ]),
            [
                'amount' => '50.00',
                'method' => 'cash',
            ]
        );

        $response->assertRedirect(
            route('login')
        );

        $this->assertDatabaseCount(
            'payments',
            0
        );
    }

    public function test_payment_above_remaining_balance_is_rejected(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '200.01',
                    'method' => 'cash',
                ]
            );

        $response->assertSessionHasErrors(
            'amount'
        );

        $this->assertDatabaseCount(
            'payments',
            0
        );

        $this->assertSame(
            InvoiceStatus::Confirmed,
            $invoice->fresh()->status
        );
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '50.00',
                    'method' => 'invalid',
                ]
            );

        $response->assertSessionHasErrors(
            'method'
        );

        $this->assertDatabaseCount(
            'payments',
            0
        );
    }

    public function test_zero_payment_is_rejected(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '0',
                    'method' => 'cash',
                ]
            );

        $response->assertSessionHasErrors(
            'amount'
        );

        $this->assertDatabaseCount(
            'payments',
            0
        );
    }

    public function test_invoice_type_mismatch_returns_not_found(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'purchase',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '50.00',
                    'method' => 'cash',
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseCount(
            'payments',
            0
        );
    }

    public function test_confirmed_invoice_page_shows_payment_form(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('invoices.show', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ])
            );

        $response->assertOk();

        $response->assertSee(
            'Record Payment'
        );
    }

    public function test_paid_invoice_page_hides_payment_form(): void
    {
        $invoice = $this->createConfirmedSaleInvoice();

        $this
            ->actingAs($this->manager)
            ->post(
                route('invoices.payments.store', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]),
                [
                    'amount' => '200.00',
                    'method' => 'cash',
                ]
            )
            ->assertRedirect();

        $response = $this
            ->actingAs($this->manager)
            ->get(
                route('invoices.show', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ])
            );

        $response->assertOk();

        $response->assertSee(
            'Payment History'
        );

        $response->assertDontSee(
            'Record Payment'
        );
    }

    private function createConfirmedSaleInvoice(): Invoice
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

    private function createConfirmedPurchaseInvoice(): Invoice
    {
        $invoice = $this->invoiceService->create(
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

        return $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }
}
