<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Command;

class CheckLowStockCommand extends Command
{
    protected $signature = 'inventory:check-low-stock';

    protected $description = 'Check products with low stock and notify active administrators and managers.';

    public function handle(): int
    {
        $products = Product::query()
            ->where(
                'is_active',
                true
            )
            ->whereColumn(
                'quantity',
                '<=',
                'reorder_level'
            )
            ->get();

        if ($products->isEmpty()) {
            $this->info(
                'No low-stock products found.'
            );

            return self::SUCCESS;
        }

        $recipients = User::query()
            ->where(
                'is_active',
                true
            )
            ->whereIn(
                'role',
                [
                    Role::Admin->value,
                    Role::Manager->value,
                ]
            )
            ->get();

        if ($recipients->isEmpty()) {
            $this->warn(
                'No active administrators or managers found.'
            );

            return self::SUCCESS;
        }

        $notificationsSent = 0;

        foreach ($products as $product) {
            foreach ($recipients as $recipient) {
                $alreadyNotified = $recipient
                    ->unreadNotifications()
                    ->where(
                        'type',
                        LowStockNotification::class
                    )
                    ->get()
                    ->contains(
                        fn ($notification): bool => (int) (
                            $notification->data['product_id'] ?? 0
                        ) === $product->id
                    );

                if ($alreadyNotified) {
                    continue;
                }

                $recipient->notify(
                    new LowStockNotification(
                        $product
                    )
                );

                $notificationsSent++;
            }
        }

        $this->info(
            sprintf(
                'Checked %d low-stock products and sent %d notifications.',
                $products->count(),
                $notificationsSent
            )
        );

        return self::SUCCESS;
    }
}
