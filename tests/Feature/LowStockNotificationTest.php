<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LowStockNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

    private Product $product;

    private StockService $stockService;

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

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'quantity' => 10,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->stockService = app(
            StockService::class
        );
    }

    public function test_admin_receives_notification_when_stock_reaches_reorder_level(): void
    {
        Notification::fake();

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -5,
            'Stock decreased to reorder level.'
        );

        Notification::assertSentTo(
            $this->admin,
            LowStockNotification::class
        );
    }

    public function test_manager_receives_notification_when_stock_reaches_reorder_level(): void
    {
        Notification::fake();

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -5,
            'Stock decreased to reorder level.'
        );

        Notification::assertSentTo(
            $this->manager,
            LowStockNotification::class
        );
    }

    public function test_cashier_does_not_receive_low_stock_notification(): void
    {
        Notification::fake();

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -5,
            'Stock decreased to reorder level.'
        );

        Notification::assertNotSentTo(
            $this->cashier,
            LowStockNotification::class
        );
    }

    public function test_inactive_admin_does_not_receive_low_stock_notification(): void
    {
        Notification::fake();

        $inactiveAdmin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => false,
        ]);

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -5,
            'Stock decreased to reorder level.'
        );

        Notification::assertNotSentTo(
            $inactiveAdmin,
            LowStockNotification::class
        );
    }

    public function test_notification_is_sent_when_stock_falls_below_reorder_level(): void
    {
        Notification::fake();

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -7,
            'Stock decreased below reorder level.'
        );

        Notification::assertSentTo(
            $this->admin,
            LowStockNotification::class
        );

        $this->assertSame(
            3,
            $this->product->fresh()->quantity
        );
    }

    public function test_notification_is_not_sent_when_stock_stays_above_reorder_level(): void
    {
        Notification::fake();

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -2,
            'Stock remains above reorder level.'
        );

        Notification::assertNothingSent();
    }

    public function test_notification_is_not_repeated_while_stock_remains_low(): void
    {
        Notification::fake();

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -5,
            'Stock reached reorder level.'
        );

        $this->stockService->adjust(
            $this->product->fresh(),
            $this->manager,
            -1,
            'Stock decreased further.'
        );

        Notification::assertSentToTimes(
            $this->admin,
            LowStockNotification::class,
            1
        );

        Notification::assertSentToTimes(
            $this->manager,
            LowStockNotification::class,
            1
        );
    }

    public function test_notification_is_sent_again_after_stock_recovers_and_drops(): void
    {
        Notification::fake();

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -5,
            'Stock reached reorder level.'
        );

        $this->stockService->adjust(
            $this->product->fresh(),
            $this->manager,
            5,
            'Stock replenished.'
        );

        $this->stockService->adjust(
            $this->product->fresh(),
            $this->manager,
            -6,
            'Stock fell below reorder level again.'
        );

        Notification::assertSentToTimes(
            $this->admin,
            LowStockNotification::class,
            2
        );

        Notification::assertSentToTimes(
            $this->manager,
            LowStockNotification::class,
            2
        );
    }

    public function test_notification_is_stored_in_database(): void
    {
        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -5,
            'Stock reached reorder level.'
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->admin->id,
                'type' => LowStockNotification::class,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->manager->id,
                'type' => LowStockNotification::class,
            ]
        );
    }

    public function test_notification_contains_product_information(): void
    {
        $this->stockService->adjust(
            $this->product,
            $this->manager,
            -6,
            'Stock decreased below reorder level.'
        );

        $notification = $this->admin
            ->fresh()
            ->notifications()
            ->latest()
            ->first();

        $this->assertNotNull(
            $notification
        );

        $this->assertSame(
            'low_stock',
            $notification->data['type']
        );

        $this->assertSame(
            $this->product->id,
            $notification->data['product_id']
        );

        $this->assertSame(
            'Test Product',
            $notification->data['product_name']
        );

        $this->assertSame(
            $this->product->sku,
            $notification->data['product_sku']
        );

        $this->assertSame(
            4,
            $notification->data['quantity']
        );

        $this->assertSame(
            5,
            $notification->data['reorder_level']
        );

        $this->assertStringContainsString(
            'Test Product',
            $notification->data['message']
        );
    }

    public function test_stock_increase_does_not_trigger_low_stock_notification(): void
    {
        Notification::fake();

        $this->stockService->adjust(
            $this->product,
            $this->manager,
            5,
            'Stock increased.'
        );

        Notification::assertNothingSent();
    }
}
