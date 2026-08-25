<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_adjustment_can_increase_stock(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        $service = new StockService;
        $service->adjust($product, $admin, 5, 'Adding stock');

        $this->assertEquals(15, $product->fresh()->quantity);
    }

    public function test_adjustment_can_decrease_stock(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        $service = new StockService;
        $service->adjust($product, $admin, -3, 'Removing stock');

        $this->assertEquals(7, $product->fresh()->quantity);
    }

    public function test_adjustment_creates_stock_movement(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        $service = new StockService;
        $adjustment = $service->adjust($product, $admin, 5, 'Stock adjustment');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => StockMovementType::Adjustment->value,
            'quantity_change' => 5,
            'quantity_before' => 10,
            'quantity_after' => 15,
            'notes' => 'Stock adjustment',
        ]);
    }

    public function test_adjustment_cannot_make_stock_negative(): void
    {
        $this->expectException(ValidationException::class);

        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 5]);

        $service = new StockService;
        $service->adjust($product, $admin, -10, 'Too much out');
    }

    public function test_adjustment_rejects_zero_quantity_change(): void
    {
        $this->expectException(ValidationException::class);

        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 5]);

        $service = new StockService;
        $service->adjust($product, $admin, 0, 'Zero change');
    }

    public function test_stock_movement_has_polymorphic_source(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        $service = new StockService;
        $adjustment = $service->adjust($product, $admin, 5, 'Source check');

        $movement = $adjustment->stockMovements()->first();

        $this->assertTrue($movement->source->is($adjustment));
    }

    public function test_multiple_adjustments_keep_correct_stock_quantity(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        $service = new StockService;
        $service->adjust($product, $admin, 5, 'First');
        $service->adjust($product, $admin, -3, 'Second');
        $service->adjust($product, $admin, 2, 'Third');

        $this->assertEquals(14, $product->fresh()->quantity);
    }

    public function test_stock_movement_cannot_be_updated(): void
    {
        $this->expectException(\LogicException::class);

        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        $service = new StockService;
        $adjustment = $service->adjust($product, $admin, 5, 'Test');

        $movement = $adjustment->stockMovements()->first();
        $movement->notes = 'Updated notes';
        $movement->save();
    }

    public function test_stock_movement_cannot_be_deleted(): void
    {
        $this->expectException(\LogicException::class);

        $admin = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        $service = new StockService;
        $adjustment = $service->adjust($product, $admin, 5, 'Test');

        $movement = $adjustment->stockMovements()->first();
        $movement->delete();
    }
}
