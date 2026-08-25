<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $costInCents = fake()->numberBetween(1000, 10000);

        $profitInCents = fake()->numberBetween(100, 5000);

        $sellInCents = $costInCents + $profitInCents;

        return [
            'category_id' => Category::factory(),

            'sku' => 'PRD-'.Str::upper(Str::random(10)),

            'name' => fake()->unique()->words(3, true),

            'description' => fake()->optional()->sentence(),

            'cost_price' => sprintf(
                '%d.%02d',
                intdiv($costInCents, 100),
                $costInCents % 100
            ),

            'sell_price' => sprintf(
                '%d.%02d',
                intdiv($sellInCents, 100),
                $sellInCents % 100
            ),

            'reorder_level' => fake()->numberBetween(1, 20),

            'image_path' => null,

            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
