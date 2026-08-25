<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\InvoiceFullyPaidNotification;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InvoiceFullyPaidNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    private InvoiceService $invoiceService;

    private PaymentService $paymentService;

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

        Setting::set(
            'tax_rate',
            '0'
        );

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
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

    public function test_admin_receives_notification_when_sales_invoice_is_fully_paid(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        Notification::fake();

        $this->payInvoice(
            $invoice,
            '200.00'
        );

        Notification::assertSentTo(
            $this->admin,
            InvoiceFullyPaidNotification::class
        );
    }

    public function test_manager_receives_notification_when_sales_invoice_is_fully_paid(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        Notification::fake();

        $this->payInvoice(
            $invoice,
            '200.00'
        );

        Notification::assertSentTo(
            $this->manager,
            InvoiceFullyPaidNotification::class
        );
    }

    public function test_cashier_does_not_receive_fully_paid_notification(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        Notification::fake();

        $this->payInvoice(
            $invoice,
            '200.00'
        );

        Notification::assertNotSentTo(
            $this->cashier,
            InvoiceFullyPaidNotification::class
        );
    }

    public function test_inactive_admin_does_not_receive_fully_paid_notification(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => false,
        ]);

        $invoice = $this->createConfirmedSalesInvoice();

        Notification::fake();

        $this->payInvoice(
            $invoice,
            '200.00'
        );

        Notification::assertNotSentTo(
            $inactiveAdmin,
            InvoiceFullyPaidNotification::class
        );
    }

    public function test_partial_payment_does_not_send_fully_paid_notification(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        Notification::fake();

        $this->payInvoice(
            $invoice,
            '50.00'
        );

        Notification::assertNotSentTo(
            $this->admin,
            InvoiceFullyPaidNotification::class
        );

        Notification::assertNotSentTo(
            $this->manager,
            InvoiceFullyPaidNotification::class
        );

        $this->assertSame(
            InvoiceStatus::PartiallyPaid,
            $invoice->fresh()->status
        );
    }

    public function test_notification_is_sent_when_multiple_payments_complete_invoice(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        Notification::fake();

        $this->payInvoice(
            $invoice,
            '50.00'
        );

        Notification::assertNotSentTo(
            $this->admin,
            InvoiceFullyPaidNotification::class
        );

        $this->payInvoice(
            $invoice->fresh(),
            '150.00'
        );

        Notification::assertSentToTimes(
            $this->admin,
            InvoiceFullyPaidNotification::class,
            1
        );

        Notification::assertSentToTimes(
            $this->manager,
            InvoiceFullyPaidNotification::class,
            1
        );

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->fresh()->status
        );
    }

    public function test_purchase_invoice_does_not_send_sales_fully_paid_notification(): void
    {
        $invoice = $this->createConfirmedPurchaseInvoice();

        Notification::fake();

        $this->payInvoice(
            $invoice,
            '100.00'
        );

        Notification::assertNotSentTo(
            $this->admin,
            InvoiceFullyPaidNotification::class
        );

        Notification::assertNotSentTo(
            $this->manager,
            InvoiceFullyPaidNotification::class
        );

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->fresh()->status
        );
    }

    public function test_fully_paid_notification_is_stored_in_database(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        $this->payInvoice(
            $invoice,
            '200.00'
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->admin->id,
                'type' => InvoiceFullyPaidNotification::class,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->manager->id,
                'type' => InvoiceFullyPaidNotification::class,
            ]
        );
    }

    public function test_fully_paid_notification_contains_invoice_information(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        $this->payInvoice(
            $invoice,
            '200.00'
        );

        $notification = $this->admin
            ->notifications()
            ->where(
                'type',
                InvoiceFullyPaidNotification::class
            )
            ->firstOrFail();

        $this->assertSame(
            'invoice_fully_paid',
            $notification->data['type']
        );

        $this->assertSame(
            $invoice->id,
            $notification->data['invoice_id']
        );

        $this->assertSame(
            $invoice->invoice_number,
            $notification->data['invoice_number']
        );

        $this->assertSame(
            'sale',
            $notification->data['invoice_type']
        );

        $this->assertSame(
            20000,
            $notification->data['total']
        );

        $this->assertSame(
            20000,
            $notification->data['paid_amount']
        );

        $this->assertStringContainsString(
            $invoice->invoice_number,
            $notification->data['message']
        );
    }

    public function test_fully_paid_notification_implements_should_queue(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        $notification = new InvoiceFullyPaidNotification(
            $invoice
        );

        $this->assertInstanceOf(
            ShouldQueue::class,
            $notification
        );
    }

    public function test_fully_paid_invoice_remains_paid_after_notification(): void
    {
        $invoice = $this->createConfirmedSalesInvoice();

        $this->payInvoice(
            $invoice,
            '200.00'
        );

        $this->assertSame(
            InvoiceStatus::Paid,
            $invoice->fresh()->status
        );

        $this->assertSame(
            0,
            $invoice->fresh()->remainingAmountInCents()
        );
    }

    private function createConfirmedSalesInvoice(): Invoice
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

    private function payInvoice(
        Invoice $invoice,
        string $amount
    ): void {
        $this->paymentService->create(
            $invoice,
            $this->manager,
            [
                'amount' => $amount,
                'method' => PaymentMethod::Cash,
            ]
        );
    }
}
