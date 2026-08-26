<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\StockAdjustmentNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {}

    public function adjust(
        Product $product,
        User $user,
        int $quantityChange,
        string $notes
    ): StockAdjustment {
        $this->ensureValidQuantityChange(
            StockMovementType::Adjustment,
            $quantityChange
        );

        return DB::transaction(function () use (
            $product,
            $user,
            $quantityChange,
            $notes
        ) {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail(
                    $product->id
                );

            $adjustment = StockAdjustment::create([
                'product_id' => $lockedProduct->id,
                'user_id' => $user->id,
                'quantity_change' => $quantityChange,
                'notes' => $notes,
            ]);

            $movement = $this->recordMovement(
                product: $lockedProduct,
                source: $adjustment,
                user: $user,
                type: StockMovementType::Adjustment,
                quantityChange: $quantityChange,
                notes: $notes,
            );

            $adjustment->setRelation(
                'stockMovements',
                collect([
                    $movement,
                ])
            );

            // إرسال إشعار تعديل المخزون
            $this->notifyStockAdjustment(
                $adjustment,
                $lockedProduct,
                $user
            );

            $this->activityLogService->record(
                actor: $user,
                action: 'stock.adjusted',
                subject: $adjustment,
                description: sprintf(
                    '%s adjusted stock for %s by %+d.',
                    $user->name,
                    $lockedProduct->name,
                    $quantityChange
                ),
                properties: [
                    'product_id' => $lockedProduct->id,
                    'product_name' => $lockedProduct->name,
                    'quantity_change' => $quantityChange,
                    'quantity_before' => $movement->quantity_before,
                    'quantity_after' => $movement->quantity_after,
                    'notes' => $notes,
                ]
            );

            return $adjustment;
        });
    }

    public function apply(
        Product $product,
        Model $source,
        User $user,
        StockMovementType $type,
        int $quantityChange,
        ?string $notes = null
    ): StockMovement {
        $this->ensureValidQuantityChange(
            $type,
            $quantityChange
        );

        return DB::transaction(function () use (
            $product,
            $source,
            $user,
            $type,
            $quantityChange,
            $notes
        ) {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail(
                    $product->id
                );

            return $this->recordMovement(
                product: $lockedProduct,
                source: $source,
                user: $user,
                type: $type,
                quantityChange: $quantityChange,
                notes: $notes,
            );
        });
    }

    private function recordMovement(
        Product $product,
        Model $source,
        User $user,
        StockMovementType $type,
        int $quantityChange,
        ?string $notes
    ): StockMovement {
        $quantityBefore = $product->quantity;
        $quantityAfter = $quantityBefore + $quantityChange;

        if ($quantityAfter < 0) {
            throw ValidationException::withMessages([
                'quantity_change' => 'Insufficient stock.',
            ]);
        }

        $movement = new StockMovement([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'notes' => $notes,
        ]);

        $movement->source()->associate(
            $source
        );

        $movement->save();

        $product->forceFill([
            'quantity' => $quantityAfter,
        ])->save();

        $this->notifyIfStockBecameLow(
            $product,
            $quantityBefore,
            $quantityAfter
        );

        return $movement;
    }

    private function notifyIfStockBecameLow(
        Product $product,
        int $quantityBefore,
        int $quantityAfter
    ): void {
        $reorderLevel = (int) $product->reorder_level;

        if ($quantityBefore <= $reorderLevel) {
            return;
        }

        if ($quantityAfter > $reorderLevel) {
            return;
        }

        $recipients = $this->notificationRecipients();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new LowStockNotification(
                $product
            )
        );
    }

    private function notifyStockAdjustment(
        StockAdjustment $adjustment,
        Product $product,
        User $user
    ): void {
        $recipients = $this->notificationRecipients();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new StockAdjustmentNotification(
                $adjustment,
                $product,
                $user
            )
        );
    }

    private function notificationRecipients()
    {
        return User::query()
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
    }

    private function ensureValidQuantityChange(
        StockMovementType $type,
        int $quantityChange
    ): void {
        if ($quantityChange === 0) {
            throw ValidationException::withMessages([
                'quantity_change' => 'Quantity change cannot be zero.',
            ]);
        }

        if (
            in_array(
                $type,
                [
                    StockMovementType::Purchase,
                    StockMovementType::Return,
                ],
                true
            )
            && $quantityChange < 0
        ) {
            throw ValidationException::withMessages([
                'quantity_change' => 'This movement must increase stock.',
            ]);
        }

        if (
            $type === StockMovementType::Sale
            && $quantityChange > 0
        ) {
            throw ValidationException::withMessages([
                'quantity_change' => 'A sale must decrease stock.',
            ]);
        }
    }
}