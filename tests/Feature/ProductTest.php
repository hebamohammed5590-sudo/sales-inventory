<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_category(): void
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertTrue(
            $product->category->is($category)
        );
    }

    public function test_category_has_many_products(): void
    {
        $category = Category::factory()->create();

        Product::factory()
            ->count(3)
            ->create([
                'category_id' => $category->id,
            ]);

        $this->assertCount(
            3,
            $category->products
        );
    }

    public function test_product_prices_are_stored_as_integer_cents(): void
    {
        $product = Product::factory()->create([
            'cost_price' => '100.50',
            'sell_price' => '150.75',
        ]);

        $this->assertSame(
            10050,
            (int) $product->getRawOriginal('cost_price')
        );

        $this->assertSame(
            15075,
            (int) $product->getRawOriginal('sell_price')
        );
    }

    public function test_product_prices_are_returned_as_decimal_strings(): void
    {
        $product = Product::factory()->create([
            'cost_price' => '100.50',
            'sell_price' => '150.75',
        ]);

        $this->assertSame(
            '100.50',
            $product->cost_price
        );

        $this->assertSame(
            '150.75',
            $product->sell_price
        );
    }

    public function test_sku_is_generated_when_not_provided(): void
    {
        $category = Category::factory()->create();

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Test Product',
            'cost_price' => '100.00',
            'sell_price' => '150.00',
            'reorder_level' => 5,
            'is_active' => true,
        ]);

        $this->assertStringStartsWith(
            'PRD-',
            $product->sku
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => $product->sku,
        ]);
    }

    public function test_provided_sku_is_preserved(): void
    {
        $product = Product::factory()->create([
            'sku' => 'CUSTOM-SKU-001',
        ]);

        $this->assertSame(
            'CUSTOM-SKU-001',
            $product->sku
        );
    }

    public function test_product_quantity_starts_at_zero(): void
    {
        $product = Product::factory()->create();

        $this->assertSame(
            0,
            $product->quantity
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 0,
        ]);
    }

    public function test_quantity_cannot_be_mass_assigned(): void
    {
        $category = Category::factory()->create();

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Protected Quantity Product',
            'cost_price' => '100.00',
            'sell_price' => '150.00',
            'reorder_level' => 5,
            'is_active' => true,
            'quantity' => 999,
        ]);

        $this->assertSame(
            0,
            $product->quantity
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 0,
        ]);
    }
}
