<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Product $product;

    private InvoiceService $invoiceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 20,
        ]);

        Setting::set(
            'tax_rate',
            '0'
        );

        $this->invoiceService = app(
            InvoiceService::class
        );
    }

    public function test_invoice_creation_is_recorded(): void
    {
        $invoice = $this->createSalesInvoice();

        $this->assertActivityExists(
            'invoice.created',
            $invoice,
            $this->admin
        );
    }

    public function test_invoice_confirmation_is_recorded(): void
    {
        $invoice = $this->createSalesInvoice();

        $this->invoiceService->confirm(
            $invoice,
            $this->admin
        );

        $this->assertActivityExists(
            'invoice.confirmed',
            $invoice,
            $this->admin
        );
    }

    public function test_invoice_cancellation_is_recorded(): void
    {
        $invoice = $this->createSalesInvoice();

        $confirmedInvoice = $this->invoiceService->confirm(
            $invoice,
            $this->admin
        );

        $this->invoiceService->cancel(
            $confirmedInvoice,
            $this->admin
        );

        $this->assertActivityExists(
            'invoice.cancelled',
            $invoice,
            $this->admin
        );
    }

    public function test_payment_creation_is_recorded(): void
    {
        $invoice = $this->createSalesInvoice();

        $confirmedInvoice = $this->invoiceService->confirm(
            $invoice,
            $this->admin
        );

        app(PaymentService::class)->create(
            $confirmedInvoice,
            $this->admin,
            [
                'amount' => '50.00',
                'method' => PaymentMethod::Cash,
            ]
        );

        $this->assertActivityExists(
            'payment.recorded',
            $invoice,
            $this->admin
        );
    }

    public function test_stock_adjustment_is_recorded(): void
    {
        $adjustment = app(StockService::class)->adjust(
            $this->product,
            $this->admin,
            5,
            'Activity log test'
        );

        $this->assertActivityExists(
            'stock.adjusted',
            $adjustment,
            $this->admin
        );
    }

    public function test_admin_can_view_activity_log_page(): void
    {
        $invoice = $this->createSalesInvoice();

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('activity-logs.index')
            );

        $response->assertOk();

        $response->assertSee(
            $invoice->invoice_number
        );

        $response->assertSee(
            $this->admin->name
        );
    }

    public function test_manager_can_view_activity_log_page(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
            'is_active' => true,
        ]);

        $this
            ->actingAs($manager)
            ->get(
                route('activity-logs.index')
            )
            ->assertOk();
    }

    public function test_cashier_cannot_view_activity_log_page(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
            'is_active' => true,
        ]);

        $this
            ->actingAs($cashier)
            ->get(
                route('activity-logs.index')
            )
            ->assertForbidden();
    }

    public function test_guest_cannot_view_activity_log_page(): void
    {
        $this
            ->get(
                route('activity-logs.index')
            )
            ->assertRedirect(
                route('login')
            );
    }

    public function test_activity_page_can_be_filtered_by_action(): void
    {
        $invoice = $this->createSalesInvoice();

        $this->invoiceService->confirm(
            $invoice,
            $this->admin
        );

        $response = $this
            ->actingAs($this->admin)
            ->get(
                route('activity-logs.index', [
                    'action' => 'invoice.confirmed',
                ])
            );

        $response->assertOk();

        $response->assertSee(
            'invoice confirmed'
        );

        // التحقق من عدم ظهور السجل الخاص بـ invoice created في جدول النتائج (داخل الـ tbody)
        $response->assertDontSeeHtml('<span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-800">
                            invoice created
                        </span>');
    }

    private function createSalesInvoice(): Invoice
    {
        return $this->invoiceService->create(
            InvoiceType::Sale,
            $this->admin,
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

    private function assertActivityExists(
        string $action,
        Invoice|StockAdjustment $subject,
        User $actor
    ): void {
        $activityLog = ActivityLog::query()
            ->where(
                'action',
                $action
            )
            ->where(
                'actor_id',
                $actor->id
            )
            ->where(
                'subject_type',
                $subject->getMorphClass()
            )
            ->where(
                'subject_id',
                $subject->id
            )
            ->first();

        $this->assertNotNull(
            $activityLog,
            "Activity [{$action}] was not recorded."
        );

        $this->assertNotEmpty(
            $activityLog->description
        );
    }
}
