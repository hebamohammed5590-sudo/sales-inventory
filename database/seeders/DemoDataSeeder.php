<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private const TARGET_PRODUCTS = 60;

    private const TARGET_CUSTOMERS = 20;

    private const TARGET_SUPPLIERS = 8;

    public function run(): void
    {
        $categories = $this->createCategories();

        $this->createProducts(
            $categories
        );

        $this->createCustomers();

        $this->createSuppliers();
    }

    private function createCategories(): array
    {
        $definitions = [
            [
                'name' => 'Electronics',

                'description' => 'Electronic devices and accessories.',
            ],

            [
                'name' => 'Computers',

                'description' => 'Computers, laptops, and computer accessories.',
            ],

            [
                'name' => 'Mobile Phones',

                'description' => 'Mobile phones and related accessories.',
            ],

            [
                'name' => 'Home Appliances',

                'description' => 'Home and kitchen appliances.',
            ],

            [
                'name' => 'Office Supplies',

                'description' => 'Office products and stationery.',
            ],

            [
                'name' => 'Accessories',

                'description' => 'General accessories and replacement parts.',
            ],

            [
                'name' => 'Furniture',

                'description' => 'Home and office furniture products.',
            ],

            [
                'name' => 'Books and Stationery',

                'description' => 'Books, notebooks, and stationery supplies.',
            ],
        ];

        $categories = [];

        foreach ($definitions as $definition) {
            $categories[] = Category::query()->firstOrCreate(
                [
                    'name' => $definition['name'],
                ],

                [
                    'description' => $definition['description'],

                    'is_active' => true,
                ]
            );
        }

        return $categories;
    }

    private function createProducts(
        array $categories
    ): void {
        $categoryCount = count(
            $categories
        );

        if ($categoryCount === 0) {
            return;
        }

        $baseProductsPerCategory = intdiv(
            self::TARGET_PRODUCTS,

            $categoryCount
        );

        $remainingProducts = self::TARGET_PRODUCTS % $categoryCount;

        foreach ($categories as $index => $category) {
            $targetForCategory = $baseProductsPerCategory;

            if ($index < $remainingProducts) {
                $targetForCategory++;
            }

            $existingProducts = Product::query()
                ->where(
                    'category_id',

                    $category->id
                )
                ->count();

            $missingProducts = max(
                0,

                $targetForCategory - $existingProducts
            );

            if ($missingProducts === 0) {
                continue;
            }

            Product::factory()
                ->count(
                    $missingProducts
                )
                ->create([
                    'category_id' => $category->id,

                    'is_active' => true,
                ]);
        }
    }

    private function createCustomers(): void
    {
        $missingCustomers = max(
            0,

            self::TARGET_CUSTOMERS - Customer::query()->count()
        );

        if ($missingCustomers === 0) {
            return;
        }

        Customer::factory()
            ->count(
                $missingCustomers
            )
            ->create();
    }

    private function createSuppliers(): void
    {
        $missingSuppliers = max(
            0,

            self::TARGET_SUPPLIERS - Supplier::query()->count()
        );

        if ($missingSuppliers === 0) {
            return;
        }

        Supplier::factory()
            ->count(
                $missingSuppliers
            )
            ->create();
    }
}
