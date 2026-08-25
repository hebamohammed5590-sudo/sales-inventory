<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use App\Notifications\StockAdjustmentNotification;
use App\Services\StockService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StockAdjustmentNotificationTest extends TestCase
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
            'quantity' => 20,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->stockService = app(
            StockService::class
        );
    }

    public function test_admin_receives_stock_adjustment_notification(): void
    {
        Notification::fake();

        $this->adjustStock(
            5,
            'Additional stock received.'
        );

        Notification::assertSentTo(
            $this->admin,
            StockAdjustmentNotification::class
        );
    }

    public function test_manager_receives_stock_adjustment_notification(): void
    {
        Notification::fake();

        $this->adjustStock(
            5,
            'Additional stock received.'
        );

        Notification::assertSentTo(
            $this->manager,
            StockAdjustmentNotification::class
        );
    }

    public function test_cashier_does_not_receive_stock_adjustment_notification(): void
    {
        Notification::fake();

        $this->adjustStock(
            5,
            'Additional stock received.'
        );

        Notification::assertNotSentTo(
            $this->cashier,
            StockAdjustmentNotification::class
        );
    }

    public function test_inactive_admin_does_not_receive_stock_adjustment_notification(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => false,
        ]);

        Notification::fake();

        $this->adjustStock(
            5,
            'Additional stock received.'
        );

        Notification::assertNotSentTo(
            $inactiveAdmin,
            StockAdjustmentNotification::class
        );
    }

    public function test_stock_increase_sends_adjustment_notification(): void
    {
        Notification::fake();

        $this->adjustStock(
            7,
            'Stock count corrected.'
        );

        Notification::assertSentTo(
            $this->admin,
            StockAdjustmentNotification::class
        );

        $this->assertSame(
            27,
            $this->product->fresh()->quantity
        );
    }

    public function test_stock_decrease_sends_adjustment_notification(): void
    {
        Notification::fake();

        $this->adjustStock(
            -3,
            'Damaged products removed.'
        );

        Notification::assertSentTo(
            $this->admin,
            StockAdjustmentNotification::class
        );

        $this->assertSame(
            17,
            $this->product->fresh()->quantity
        );
    }

    public function test_stock_adjustment_notification_is_stored_in_database(): void
    {
        $this->adjustStock(
            5,
            'Additional stock received.'
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->admin->id,
                'type' => StockAdjustmentNotification::class,
            ]
        );

        $this->assertDatabaseHas(
            'notifications',
            [
                'notifiable_type' => User::class,
                'notifiable_id' => $this->manager->id,
                'type' => StockAdjustmentNotification::class,
            ]
        );
    }

    public function test_stock_adjustment_notification_contains_product_information(): void
    {
        $adjustment = $this->stockService->adjust(
            $this->product,
            $this->manager,
            5,
            'Additional stock received.'
        );

        $notification = $this->admin
            ->notifications()
            ->where(
                'type',
                StockAdjustmentNotification::class
            )
            ->firstOrFail();

        $this->assertSame(
            'stock_adjustment',
            $notification->data['type']
        );

        $this->assertSame(
            $adjustment->id,
            $notification->data['adjustment_id']
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
            5,
            $notification->data['quantity_change']
        );

        $this->assertSame(
            25,
            $notification->data['quantity_after']
        );
    }

    public function test_stock_adjustment_notification_contains_user_information(): void
    {
        $this->adjustStock(
            5,
            'Additional stock received.'
        );

        $notification = $this->admin
            ->notifications()
            ->where(
                'type',
                StockAdjustmentNotification::class
            )
            ->firstOrFail();

        $this->assertSame(
            $this->manager->id,
            $notification->data['user_id']
        );

        $this->assertSame(
            $this->manager->name,
            $notification->data['user_name']
        );
    }

    public function test_stock_adjustment_notification_contains_adjustment_notes(): void
    {
        $this->adjustStock(
            -4,
            'Four damaged units removed.'
        );

        $notification = $this->admin
            ->notifications()
            ->where(
                'type',
                StockAdjustmentNotification::class
            )
            ->firstOrFail();

        $this->assertSame(
            'Four damaged units removed.',
            $notification->data['notes']
        );

        $this->assertStringContainsString(
            'Test Product',
            $notification->data['message']
        );
    }

    public function test_each_stock_adjustment_sends_a_separate_notification(): void
    {
        Notification::fake();

        $this->adjustStock(
            5,
            'First adjustment.'
        );

        $this->adjustStock(
            -2,
            'Second adjustment.'
        );

        Notification::assertSentToTimes(
            $this->admin,
            StockAdjustmentNotification::class,
            2
        );

        Notification::assertSentToTimes(
            $this->manager,
            StockAdjustmentNotification::class,
            2
        );
    }

    public function test_stock_adjustment_notification_implements_should_queue(): void
    {
        $adjustment = $this->stockService->adjust(
            $this->product,
            $this->manager,
            5,
            'Additional stock received.'
        );

        $notification = new StockAdjustmentNotification(
            $adjustment,
            $this->product->fresh(),
            $this->manager
        );

        $this->assertInstanceOf(
            ShouldQueue::class,
            $notification
        );
    }

    private function adjustStock(
        int $quantityChange,
        string $notes
    ): void {
        $this->stockService->adjust(
            $this->product,
            $this->manager,
            $quantityChange,
            $notes
        );
    }
}
