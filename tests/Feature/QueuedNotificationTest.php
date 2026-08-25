<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use App\Notifications\DailyReportNotification;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Queued Product',
            'quantity' => 3,
            'reorder_level' => 5,
            'is_active' => true,
        ]);
    }

    public function test_low_stock_notification_implements_should_queue(): void
    {
        $notification = new LowStockNotification(
            $this->product
        );

        $this->assertInstanceOf(
            ShouldQueue::class,
            $notification
        );
    }

    public function test_daily_report_notification_implements_should_queue(): void
    {
        $notification = new DailyReportNotification(
            $this->reportData()
        );

        $this->assertInstanceOf(
            ShouldQueue::class,
            $notification
        );
    }

    public function test_low_stock_notification_is_dispatched_to_queue(): void
    {
        Queue::fake();

        $this->admin->notify(
            new LowStockNotification(
                $this->product
            )
        );

        Queue::assertPushed(
            SendQueuedNotifications::class,
            function (SendQueuedNotifications $job): bool {
                return $job->notification instanceof LowStockNotification;
            }
        );
    }

    public function test_daily_report_notification_is_dispatched_to_queue(): void
    {
        Queue::fake();

        $this->admin->notify(
            new DailyReportNotification(
                $this->reportData()
            )
        );

        Queue::assertPushed(
            SendQueuedNotifications::class,
            function (SendQueuedNotifications $job): bool {
                return $job->notification instanceof DailyReportNotification;
            }
        );
    }

    public function test_queued_low_stock_notification_targets_correct_user(): void
    {
        Queue::fake();

        $this->admin->notify(
            new LowStockNotification(
                $this->product
            )
        );

        Queue::assertPushed(
            SendQueuedNotifications::class,
            function (SendQueuedNotifications $job): bool {
                return $job->notifiables->contains(
                    fn (User $user): bool => $user->id === $this->admin->id
                );
            }
        );
    }

    public function test_queued_daily_report_notification_uses_database_channel(): void
    {
        Queue::fake();

        $this->admin->notify(
            new DailyReportNotification(
                $this->reportData()
            )
        );

        Queue::assertPushed(
            SendQueuedNotifications::class,
            function (SendQueuedNotifications $job): bool {
                return in_array(
                    'database',
                    $job->channels,
                    true
                );
            }
        );
    }

    private function reportData(): array
    {
        return [
            'date' => now()->subDay()->toDateString(),
            'sales_total' => 30000,
            'sales_count' => 3,
            'purchases_total' => 15000,
            'purchases_count' => 2,
            'profit' => 10000,
            'low_stock_count' => 1,
        ];
    }
}
