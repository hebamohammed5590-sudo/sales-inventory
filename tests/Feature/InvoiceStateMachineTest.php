<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InvoiceStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Customer $customer;

    private Product $product;

    private InvoiceService $invoiceService;

    private PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create([
            'role' => Role::Manager,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 20,
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        $this->invoiceService = app(
            InvoiceService::class
        );

        $this->paymentService = app(
            PaymentService::class
        );
    }

    public function test_new_invoice_starts_as_draft(): void
    {
        $invoice = $this->createDraftInvoice();

        $this->assertSame(
            InvoiceStatus::Draft,
            $invoice->status
        );
    }

    public function test_draft_invoice_can_be_confirmed(): void
    {
        $invoice = $this->createDraftInvoice();

        $confirmedInvoice = $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );

        $this->assertSame(
            InvoiceStatus::Confirmed,
            $confirmedInvoice->status
        );

        $this->assertNotNull(
            $confirmedInvoice->confirmed_at
        );
    }

    public function test_draft_invoice_can_be_cancelled(): void
    {
        $invoice = $this->createDraftInvoice();

        $cancelledInvoice = $this->invoiceService->cancel(
            $invoice,
            $this->manager
        );

        $this->assertSame(
            InvoiceStatus::Cancelled,
            $cancelledInvoice->status
        );

        $this->assertNotNull(
            $cancelledInvoice->cancelled_at
        );

        $this->assertSame(
            20,
            $this->product->fresh()->quantity
        );
    }

    public function test_confirmed_invoice_can_become_partially_paid(): void
    {
        $invoice = $this->createConfirmedInvoice();

        $this->paymentService->create(
            $invoice,
            $this->manager,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );

        $this->assertSame(
            InvoiceStatus::PartiallyPaid,
            $invoice->fresh()->status
        );
    }

    public function test_confirmed_invoice_can_become_paid_directly(): void
    {
        $invoice = $this->createConfirmedInvoice();

        $this->paymentService->create(
            $invoice,
            $this->manager,
            [
                'amount' => $invoice->total,
                'method' => PaymentMethod::Cash,
            ]
        );

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->fresh()->status
        );
    }

    public function test_partially_paid_invoice_can_become_paid(): void
    {
        $invoice = $this->createConfirmedInvoice();

        $this->paymentService->create(
            $invoice,
            $this->manager,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );

        $partiallyPaidInvoice = $invoice->fresh();

        $this->assertSame(
            InvoiceStatus::PartiallyPaid,
            $partiallyPaidInvoice->status
        );

        $this->paymentService->create(
            $partiallyPaidInvoice,
            $this->manager,
            [
                'amount' => $partiallyPaidInvoice->remainingAmount(),
                'method' => PaymentMethod::Card,
            ]
        );

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->fresh()->status
        );
    }

    public function test_confirmed_invoice_can_be_cancelled(): void
    {
        $invoice = $this->createConfirmedInvoice();

        $this->assertSame(
            18,
            $this->product->fresh()->quantity
        );

        $cancelledInvoice = $this->invoiceService->cancel(
            $invoice,
            $this->manager
        );

        $this->assertSame(
            InvoiceStatus::Cancelled,
            $cancelledInvoice->status
        );

        $this->assertSame(
            20,
            $this->product->fresh()->quantity
        );
    }

    public function test_draft_invoice_cannot_receive_payment(): void
    {
        $invoice = $this->createDraftInvoice();

        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $invoice,
            $this->manager,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );
    }

    public function test_confirmed_invoice_cannot_be_confirmed_again(): void
    {
        $invoice = $this->createConfirmedInvoice();

        $this->expectException(
            ValidationException::class
        );

        $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }

    public function test_paid_invoice_cannot_be_confirmed_again(): void
    {
        $invoice = $this->createPaidInvoice();

        $this->expectException(
            ValidationException::class
        );

        $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }

    public function test_paid_invoice_cannot_be_cancelled(): void
    {
        $invoice = $this->createPaidInvoice();

        $this->expectException(
            ValidationException::class
        );

        $this->invoiceService->cancel(
            $invoice,
            $this->manager
        );
    }

    public function test_paid_invoice_cannot_receive_another_payment(): void
    {
        $invoice = $this->createPaidInvoice();

        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $invoice,
            $this->manager,
            [
                'amount' => '1.00',
                'method' => PaymentMethod::Cash,
            ]
        );
    }

    public function test_cancelled_invoice_cannot_be_confirmed(): void
    {
        $invoice = $this->createDraftInvoice();

        $cancelledInvoice = $this->invoiceService->cancel(
            $invoice,
            $this->manager
        );

        $this->expectException(
            ValidationException::class
        );

        $this->invoiceService->confirm(
            $cancelledInvoice,
            $this->manager
        );
    }

    public function test_cancelled_invoice_cannot_be_cancelled_again(): void
    {
        $invoice = $this->createDraftInvoice();

        $cancelledInvoice = $this->invoiceService->cancel(
            $invoice,
            $this->manager
        );

        $this->expectException(
            ValidationException::class
        );

        $this->invoiceService->cancel(
            $cancelledInvoice,
            $this->manager
        );
    }

    public function test_cancelled_invoice_cannot_receive_payment(): void
    {
        $invoice = $this->createDraftInvoice();

        $cancelledInvoice = $this->invoiceService->cancel(
            $invoice,
            $this->manager
        );

        $this->expectException(
            ValidationException::class
        );

        $this->paymentService->create(
            $cancelledInvoice,
            $this->manager,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );
    }

    public function test_draft_status_allows_only_confirmation_or_cancellation(): void
    {
        $this->assertTrue(
            InvoiceStatus::Draft->canTransitionTo(
                InvoiceStatus::Confirmed
            )
        );

        $this->assertTrue(
            InvoiceStatus::Draft->canTransitionTo(
                InvoiceStatus::Cancelled
            )
        );

        $this->assertFalse(
            InvoiceStatus::Draft->canTransitionTo(
                InvoiceStatus::PartiallyPaid
            )
        );

        $this->assertFalse(
            InvoiceStatus::Draft->canTransitionTo(
                InvoiceStatus::Paid
            )
        );
    }

    public function test_confirmed_status_allows_expected_transitions(): void
    {
        $this->assertTrue(
            InvoiceStatus::Confirmed->canTransitionTo(
                InvoiceStatus::PartiallyPaid
            )
        );

        $this->assertTrue(
            InvoiceStatus::Confirmed->canTransitionTo(
                InvoiceStatus::Paid
            )
        );

        $this->assertTrue(
            InvoiceStatus::Confirmed->canTransitionTo(
                InvoiceStatus::Cancelled
            )
        );

        $this->assertFalse(
            InvoiceStatus::Confirmed->canTransitionTo(
                InvoiceStatus::Draft
            )
        );
    }

    public function test_partially_paid_status_allows_expected_transitions(): void
    {
        $this->assertTrue(
            InvoiceStatus::PartiallyPaid->canTransitionTo(
                InvoiceStatus::Paid
            )
        );

        $this->assertTrue(
            InvoiceStatus::PartiallyPaid->canTransitionTo(
                InvoiceStatus::Cancelled
            )
        );

        $this->assertFalse(
            InvoiceStatus::PartiallyPaid->canTransitionTo(
                InvoiceStatus::Draft
            )
        );

        $this->assertFalse(
            InvoiceStatus::PartiallyPaid->canTransitionTo(
                InvoiceStatus::Confirmed
            )
        );
    }

    public function test_paid_status_is_final(): void
    {
        $this->assertSame(
            [],
            InvoiceStatus::Paid->allowedTransitions()
        );

        foreach (InvoiceStatus::cases() as $status) {
            $this->assertFalse(
                InvoiceStatus::Paid->canTransitionTo(
                    $status
                )
            );
        }
    }

    public function test_cancelled_status_is_final(): void
    {
        $this->assertSame(
            [],
            InvoiceStatus::Cancelled->allowedTransitions()
        );

        foreach (InvoiceStatus::cases() as $status) {
            $this->assertFalse(
                InvoiceStatus::Cancelled->canTransitionTo(
                    $status
                )
            );
        }
    }

    private function createDraftInvoice(): Invoice
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

    private function createConfirmedInvoice(): Invoice
    {
        return $this->invoiceService->confirm(
            $this->createDraftInvoice(),
            $this->manager
        );
    }

    private function createPaidInvoice(): Invoice
    {
        $invoice = $this->createConfirmedInvoice();

        $this->paymentService->create(
            $invoice,
            $this->manager,
            [
                'amount' => $invoice->total,
                'method' => PaymentMethod::Cash,
            ]
        );

        return $invoice->fresh();
    }
}
