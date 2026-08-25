<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\DailyReportNotification;
use App\Services\InvoiceService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyReportCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    private InvoiceService $invoiceService;

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

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 20,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->invoiceService = app(
            InvoiceService::class
        );
    }

    public function test_command_sends_daily_report_to_admin(): void
    {
        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $this->assertDatabaseHas(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->admin->id,
                'type' => DailyReportNotification::class,
            ]
        );
    }

    public function test_command_sends_daily_report_to_manager(): void
    {
        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $this->assertDatabaseHas(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->manager->id,
                'type' => DailyReportNotification::class,
            ]
        );
    }

    public function test_command_does_not_send_daily_report_to_cashier(): void
    {
        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $this->assertDatabaseMissing(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->cashier->id,
                'type' => DailyReportNotification::class,
            ]
        );
    }

    public function test_command_does_not_send_daily_report_to_inactive_users(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => false,
        ]);

        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $this->assertDatabaseMissing(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $inactiveAdmin->id,
                'type' => DailyReportNotification::class,
            ]
        );
    }

    public function test_daily_report_contains_yesterday_sales(): void
    {
        $this->createConfirmedSale(
            3,
            now()->subDay()->toDateString()
        );

        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $report = $this->dailyReportData();

        $this->assertSame(
            now()->subDay()->toDateString(),
            $report['date']
        );

        $this->assertSame(
            1,
            $report['sales_count']
        );

        $this->assertSame(
            30000,
            $report['sales_total']
        );
    }

    public function test_daily_report_contains_yesterday_purchases(): void
    {
        $this->createConfirmedPurchase(
            4,
            now()->subDay()->toDateString()
        );

        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $report = $this->dailyReportData();

        $this->assertSame(
            1,
            $report['purchases_count']
        );

        $this->assertSame(
            20000,
            $report['purchases_total']
        );
    }

    public function test_daily_report_contains_yesterday_profit(): void
    {
        $this->createConfirmedSale(
            3,
            now()->subDay()->toDateString()
        );

        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $report = $this->dailyReportData();

        $this->assertSame(
            15000,
            $report['profit']
        );
    }

    public function test_daily_report_excludes_today_sales(): void
    {
        $this->createConfirmedSale(
            2,
            now()->toDateString()
        );

        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $report = $this->dailyReportData();

        $this->assertSame(
            0,
            $report['sales_count']
        );

        $this->assertSame(
            0,
            $report['sales_total']
        );
    }

    public function test_daily_report_counts_low_stock_products(): void
    {
        Product::factory()->create([
            'quantity' => 3,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $report = $this->dailyReportData();

        $this->assertSame(
            1,
            $report['low_stock_count']
        );
    }

    public function test_daily_report_is_not_duplicated_for_same_date(): void
    {
        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $this->assertSame(
            1,
            $this->admin
                ->fresh()
                ->notifications()
                ->where(
                    'type',
                    DailyReportNotification::class
                )
                ->count()
        );

        $this->assertSame(
            1,
            $this->manager
                ->fresh()
                ->notifications()
                ->where(
                    'type',
                    DailyReportNotification::class
                )
                ->count()
        );
    }

    public function test_daily_report_notification_contains_readable_message(): void
    {
        $this->createConfirmedSale(
            2,
            now()->subDay()->toDateString()
        );

        $this->artisan(
            'inventory:daily-report'
        )->assertSuccessful();

        $report = $this->dailyReportData();

        $this->assertSame(
            'daily_report',
            $report['type']
        );

        $this->assertStringContainsString(
            'Daily report',
            $report['message']
        );

        $this->assertStringContainsString(
            '200.00',
            $report['message']
        );
    }

    public function test_daily_report_command_is_scheduled_at_eight(): void
    {
        $events = app(
            Schedule::class
        )->events();

        $event = collect(
            $events
        )->first(
            fn ($event): bool => str_contains(
                $event->command ?? '',
                'inventory:daily-report'
            )
        );

        $this->assertNotNull(
            $event
        );

        $this->assertSame(
            '0 8 * * *',
            $event->expression
        );
    }

    private function dailyReportData(): array
    {
        $notification = $this->admin
            ->fresh()
            ->notifications()
            ->where(
                'type',
                DailyReportNotification::class
            )
            ->first();

        $this->assertNotNull(
            $notification
        );

        return $notification->data;
    }

    private function createConfirmedSale(
        int $quantity,
        string $date
    ): Invoice {
        $invoice = $this->invoiceService->create(
            InvoiceType::Sale,
            $this->manager,
            [
                'customer_id' => $this->customer->id,
                'invoice_date' => $date,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => $quantity,
                    ],
                ],
            ]
        );

        return $this->invoiceService->confirm(
            $invoice,
            $this->manager
        );
    }

    private function createConfirmedPurchase(
        int $quantity,
        string $date
    ): Invoice {
        $invoice = $this->invoiceService->create(
            InvoiceType::Purchase,
            $this->manager,
            [
                'supplier_id' => $this->supplier->id,
                'invoice_date' => $date,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => $quantity,
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
