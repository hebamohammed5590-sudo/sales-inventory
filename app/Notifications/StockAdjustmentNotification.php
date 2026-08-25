<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StockAdjustmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly StockAdjustment $adjustment,
        private readonly Product $product,
        private readonly User $user
    ) {}

    public function via(
        object $notifiable
    ): array {
        return [
            'database',
        ];
    }

    public function toArray(
        object $notifiable
    ): array {
        $quantityChange = (int) $this->adjustment->quantity_change;

        return [
            'type' => 'stock_adjustment',

            'adjustment_id' => $this->adjustment->id,

            'product_id' => $this->product->id,

            'product_name' => $this->product->name,

            'product_sku' => $this->product->sku,

            'quantity_change' => $quantityChange,

            'quantity_after' => $this->product->quantity,

            'user_id' => $this->user->id,

            'user_name' => $this->user->name,

            'notes' => $this->adjustment->notes,

            'message' => sprintf(
                'Stock adjustment for product "%s": %s%d. Current stock: %d. Performed by %s.',

                $this->product->name,

                $quantityChange > 0 ? '+' : '',

                $quantityChange,

                $this->product->quantity,

                $this->user->name
            ),
        ];
    }
}
