<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StockLedgerConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Product $product;

    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->manager = User::factory()->create([
            'role' => Role::Manager,
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'quantity' => 0,
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        $this->stockService = app(
            StockService::class
        );
    }

    public function test_product_quantity_matches_stock_ledger_after_twenty_random_operations(): void
    {
        $operations = $this->generateSafeOperations(
            20
        );

        foreach ($operations as $index => $quantityChange) {
            $this->stockService->adjust(
                $this->product,
                $this->manager,
                $quantityChange,
                sprintf(
                    'Random stock operation #%d',
                    $index + 1
                )
            );
        }

        $ledgerTotal = (int) StockMovement::query()
            ->where(
                'product_id',
                $this->product->id
            )
            ->sum(
                'quantity_change'
            );

        $productQuantity = $this->product
            ->fresh()
            ->quantity;

        $movementCount = StockMovement::query()
            ->where(
                'product_id',
                $this->product->id
            )
            ->count();

        $this->assertSame(
            20,
            $movementCount
        );

        $this->assertSame(
            $ledgerTotal,
            $productQuantity
        );

        $this->assertSame(
            array_sum(
                $operations
            ),
            $productQuantity
        );

        $this->assertGreaterThanOrEqual(
            0,
            $productQuantity
        );
    }

    public function test_every_random_operation_records_correct_before_and_after_quantities(): void
    {
        $operations = $this->generateSafeOperations(
            20
        );

        $expectedQuantity = 0;

        foreach ($operations as $index => $quantityChange) {
            $quantityBefore = $expectedQuantity;

            $adjustment = $this->stockService->adjust(
                $this->product,
                $this->manager,
                $quantityChange,
                sprintf(
                    'Ledger verification operation #%d',
                    $index + 1
                )
            );

            $expectedQuantity += $quantityChange;

            $movement = $adjustment
                ->stockMovements
                ->first();

            $this->assertNotNull(
                $movement
            );

            $this->assertSame(
                $quantityBefore,
                $movement->quantity_before
            );

            $this->assertSame(
                $expectedQuantity,
                $movement->quantity_after
            );

            $this->assertSame(
                $quantityChange,
                $movement->quantity_change
            );

            $this->assertSame(
                $expectedQuantity,
                $this->product->fresh()->quantity
            );

            $this->assertGreaterThanOrEqual(
                0,
                $movement->quantity_after
            );
        }
    }

    public function test_stock_ledger_contains_both_increases_and_decreases(): void
    {
        $operations = $this->generateSafeOperations(
            20
        );

        foreach ($operations as $index => $quantityChange) {
            $this->stockService->adjust(
                $this->product,
                $this->manager,
                $quantityChange,
                sprintf(
                    'Mixed stock operation #%d',
                    $index + 1
                )
            );
        }

        $increases = StockMovement::query()
            ->where(
                'product_id',
                $this->product->id
            )
            ->where(
                'quantity_change',
                '>',
                0
            )
            ->count();

        $decreases = StockMovement::query()
            ->where(
                'product_id',
                $this->product->id
            )
            ->where(
                'quantity_change',
                '<',
                0
            )
            ->count();

        $this->assertGreaterThan(
            0,
            $increases
        );

        $this->assertGreaterThan(
            0,
            $decreases
        );

        $this->assertSame(
            20,
            $increases + $decreases
        );
    }

    public function test_product_with_initial_stock_matches_initial_quantity_plus_ledger(): void
    {
        $initialQuantity = 25;

        $product = Product::factory()->create([
            'quantity' => $initialQuantity,
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        $operations = $this->generateSafeOperations(
            20,
            $initialQuantity
        );

        foreach ($operations as $index => $quantityChange) {
            $this->stockService->adjust(
                $product,
                $this->manager,
                $quantityChange,
                sprintf(
                    'Initial stock operation #%d',
                    $index + 1
                )
            );
        }

        $ledgerTotal = (int) StockMovement::query()
            ->where(
                'product_id',
                $product->id
            )
            ->sum(
                'quantity_change'
            );

        $this->assertSame(
            $initialQuantity + $ledgerTotal,
            $product->fresh()->quantity
        );

        $this->assertSame(
            20,
            StockMovement::query()
                ->where(
                    'product_id',
                    $product->id
                )
                ->count()
        );
    }

    public function test_stock_ledgers_remain_isolated_between_products(): void
    {
        $otherProduct = Product::factory()->create([
            'quantity' => 0,
            'reorder_level' => 0,
            'is_active' => true,
        ]);

        $firstOperations = $this->generateSafeOperations(
            20
        );

        $secondOperations = $this->generateSafeOperations(
            20
        );

        foreach ($firstOperations as $index => $quantityChange) {
            $this->stockService->adjust(
                $this->product,
                $this->manager,
                $quantityChange,
                sprintf(
                    'First product operation #%d',
                    $index + 1
                )
            );
        }

        foreach ($secondOperations as $index => $quantityChange) {
            $this->stockService->adjust(
                $otherProduct,
                $this->manager,
                $quantityChange,
                sprintf(
                    'Second product operation #%d',
                    $index + 1
                )
            );
        }

        $firstLedgerTotal = (int) StockMovement::query()
            ->where(
                'product_id',
                $this->product->id
            )
            ->sum(
                'quantity_change'
            );

        $secondLedgerTotal = (int) StockMovement::query()
            ->where(
                'product_id',
                $otherProduct->id
            )
            ->sum(
                'quantity_change'
            );

        $this->assertSame(
            $firstLedgerTotal,
            $this->product->fresh()->quantity
        );

        $this->assertSame(
            $secondLedgerTotal,
            $otherProduct->fresh()->quantity
        );

        $this->assertSame(
            40,
            StockMovement::query()->count()
        );
    }

    private function generateSafeOperations(
        int $count,
        int $initialQuantity = 0
    ): array {
        $operations = [];

        $currentQuantity = $initialQuantity;

        for ($index = 0; $index < $count; $index++) {
            if ($currentQuantity === 0) {
                $quantityChange = random_int(
                    1,
                    10
                );
            } elseif ($index % 2 === 0) {
                $quantityChange = random_int(
                    1,
                    10
                );
            } else {
                $maximumDecrease = min(
                    $currentQuantity,
                    10
                );

                $quantityChange = -random_int(
                    1,
                    $maximumDecrease
                );
            }

            $currentQuantity += $quantityChange;

            $operations[] = $quantityChange;
        }

        return $operations;
    }
}
