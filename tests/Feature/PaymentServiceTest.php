<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    private PaymentService $paymentService;

    private User $user;

    private Customer $customer;

    private Product $product;

    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = app(
            InvoiceService::class
        );

        $this->paymentService = app(
            PaymentService::class
        );

        $this->user = User::factory()->create();

        $this->customer = Customer::factory()->create();

        Setting::set(
            'tax_rate',
            '0'
        );

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
        ]);

        $draftInvoice = $this->invoiceService->create(
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

        $this->invoice = $this->invoiceService->confirm(
            $draftInvoice,
            $this->user
        );
    }

    public function test_can_create_partial_payment(): void
    {
        $payment = $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );

        $this->assertInstanceOf(
            Payment::class,
            $payment
        );

        $this->assertSame(
            '50.00',
            $payment->amount
        );

        $this->assertSame(
            InvoiceStatus::PartiallyPaid,
            $this->invoice->fresh()->status
        );

        $this->assertSame(
            '150.00',
            $this->invoice->fresh()->remainingAmount()
        );
    }

    public function test_full_payment_marks_invoice_as_paid(): void
    {
        $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '200.00',
                'method' => PaymentMethod::Card,
            ]
        );

        $invoice = $this->invoice->fresh();

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->status
        );

        $this->assertSame(
            '200.00',
            $invoice->paidAmount()
        );

        $this->assertSame(
            '0.00',
            $invoice->remainingAmount()
        );
    }

    public function test_multiple_payments_can_complete_invoice(): void
    {
        $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '75.00',
                'method' => PaymentMethod::Cash,
            ]
        );

        $this->paymentService->create(
            $this->invoice->fresh(),
            $this->user,
            [
                'amount' => '125.00',
                'method' => PaymentMethod::BankTransfer,
            ]
        );

        $invoice = $this->invoice->fresh();

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->status
        );

        $this->assertSame(
            '200.00',
            $invoice->paidAmount()
        );

        $this->assertDatabaseCount(
            'payments',
            2
        );
    }

    public function test_payment_cannot_exceed_remaining_balance(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '200.01',
                'method' => PaymentMethod::Cash,
            ]
        );
    }

    public function test_payment_amount_must_be_greater_than_zero(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '0.00',
                'method' => PaymentMethod::Cash,
            ]
        );
    }

    public function test_invalid_payment_method_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '50.00',
                'method' => 'invalid_method',
            ]
        );
    }

    public function test_draft_invoice_cannot_receive_payment(): void
    {
        $draftInvoice = $this->invoiceService->create(
            InvoiceType::Sale,
            $this->user,
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

        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $draftInvoice,
            $this->user,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );
    }

    public function test_cancelled_invoice_cannot_receive_payment(): void
    {
        $cancelledInvoice = $this->invoiceService->cancel(
            $this->invoice,
            $this->user
        );

        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $cancelledInvoice,
            $this->user,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );
    }

    public function test_paid_invoice_cannot_receive_another_payment(): void
    {
        $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '200.00',
                'method' => PaymentMethod::Cash,
            ]
        );

        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $this->invoice->fresh(),
            $this->user,
            [
                'amount' => '1.00',
                'method' => PaymentMethod::Cash,
            ]
        );
    }

    public function test_payment_has_polymorphic_invoice_relationship(): void
    {
        $payment = $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );

        $this->assertInstanceOf(
            Invoice::class,
            $payment->payable
        );

        $this->assertTrue(
            $payment->payable->is(
                $this->invoice
            )
        );

        $this->assertDatabaseHas('payments', [
            'payable_type' => Invoice::class,
            'payable_id' => $this->invoice->id,
            'user_id' => $this->user->id,
            'amount' => 5000,
        ]);
    }

    public function test_payment_can_store_reference_and_notes(): void
    {
        $payment = $this->paymentService->create(
            $this->invoice,
            $this->user,
            [
                'amount' => '50.00',

                'method' => PaymentMethod::BankTransfer,

                'reference' => 'BANK-12345',

                'notes' => 'First installment.',
            ]
        );

        $this->assertSame(
            PaymentMethod::BankTransfer,
            $payment->method
        );

        $this->assertSame(
            'BANK-12345',
            $payment->reference
        );

        $this->assertSame(
            'First installment.',
            $payment->notes
        );
    }
}
