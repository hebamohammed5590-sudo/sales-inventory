<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckLowStockCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

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
    }

    public function test_command_reports_when_no_products_have_low_stock(): void
    {
        Product::factory()->create([
            'quantity' => 10,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->artisan('inventory:check-low-stock')
            ->expectsOutput('No low-stock products found.')
            ->assertSuccessful();
    }

    public function test_command_notifies_admin_when_product_has_low_stock(): void
    {
        $product = $this->createLowStockProduct();

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->admin->id,
            'type' => LowStockNotification::class,
        ]);

        $notification = $this->admin
            ->fresh()
            ->notifications()
            ->first();

        $this->assertNotNull($notification);

        $this->assertSame(
            $product->id,
            $notification->data['product_id']
        );
    }

    public function test_command_notifies_manager_when_product_has_low_stock(): void
    {
        $this->createLowStockProduct();

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->manager->id,
            'type' => LowStockNotification::class,
        ]);
    }

    public function test_command_does_not_notify_cashier(): void
    {
        $this->createLowStockProduct();

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->cashier->id,
            'type' => LowStockNotification::class,
        ]);
    }

    public function test_command_does_not_notify_inactive_admin(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => false,
        ]);

        $this->createLowStockProduct();

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $inactiveAdmin->id,
            'type' => LowStockNotification::class,
        ]);
    }

    public function test_command_ignores_inactive_products(): void
    {
        Product::factory()->create([
            'quantity' => 2,
            'reorder_level' => 5,
            'is_active' => false,
        ]);

        $this->artisan('inventory:check-low-stock')
            ->expectsOutput('No low-stock products found.')
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'notifications',
            0
        );
    }

    public function test_command_notifies_when_quantity_equals_reorder_level(): void
    {
        Product::factory()->create([
            'quantity' => 5,
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'notifications',
            2
        );
    }

    public function test_command_does_not_duplicate_unread_notifications(): void
    {
        $this->createLowStockProduct();

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->assertSame(
            1,
            $this->admin
                ->fresh()
                ->unreadNotifications()
                ->count()
        );

        $this->assertSame(
            1,
            $this->manager
                ->fresh()
                ->unreadNotifications()
                ->count()
        );

        $this->assertDatabaseCount(
            'notifications',
            2
        );
    }

    public function test_command_sends_new_notification_after_previous_notification_is_read(): void
    {
        $this->createLowStockProduct();

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->admin
            ->fresh()
            ->unreadNotifications
            ->each
            ->markAsRead();

        $this->artisan('inventory:check-low-stock')
            ->assertSuccessful();

        $this->assertSame(
            2,
            $this->admin
                ->fresh()
                ->notifications()
                ->count()
        );

        $this->assertSame(
            1,
            $this->manager
                ->fresh()
                ->notifications()
                ->count()
        );
    }

    public function test_command_notifies_for_each_low_stock_product(): void
    {
        Product::factory()
            ->count(3)
            ->create([
                'quantity' => 2,
                'reorder_level' => 5,
                'is_active' => true,
            ]);

        $this->artisan('inventory:check-low-stock')
            ->expectsOutput(
                'Checked 3 low-stock products and sent 6 notifications.'
            )
            ->assertSuccessful();

        $this->assertDatabaseCount(
            'notifications',
            6
        );
    }

    public function test_low_stock_command_is_scheduled_daily_at_eight(): void
    {
        $events = app(
            Schedule::class
        )->events();

        $event = collect($events)
            ->first(
                fn ($event): bool => str_contains(
                    $event->command ?? '',
                    'inventory:check-low-stock'
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

    private function createLowStockProduct(): Product
    {
        return Product::factory()->create([
            'name' => 'Low Stock Product',
            'quantity' => 3,
            'reorder_level' => 5,
            'is_active' => true,
        ]);
    }
}
