<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_products(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->get(route('products.index'))
            ->assertOk();
    }

    public function test_manager_can_view_products(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->get(route('products.index'))
            ->assertOk();
    }

    public function test_cashier_cannot_view_products(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($cashier)
            ->get(route('products.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_products(): void
    {
        $this->get(route('products.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(
                route('products.store'),
                $this->productData($category)
            )
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Wireless Mouse',
            'category_id' => $category->id,
            'cost_price' => 10050,
            'sell_price' => 15075,
            'quantity' => 0,
        ]);
    }

    public function test_manager_can_create_product(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($manager)
            ->post(
                route('products.store'),
                $this->productData($category, [
                    'name' => 'Manager Product',
                ])
            )
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Manager Product',
        ]);
    }

    public function test_cashier_cannot_create_product(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($cashier)
            ->post(
                route('products.store'),
                $this->productData($category)
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('products', [
            'name' => 'Wireless Mouse',
        ]);
    }

    public function test_sku_is_generated_when_left_blank(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(
                route('products.store'),
                $this->productData($category, [
                    'sku' => '',
                ])
            )
            ->assertRedirect(route('products.index'));

        $product = Product::query()
            ->where('name', 'Wireless Mouse')
            ->firstOrFail();

        $this->assertStringStartsWith(
            'PRD-',
            $product->sku
        );
    }

    public function test_product_sku_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        Product::factory()->create([
            'sku' => 'DUPLICATE-SKU',
        ]);

        $this->actingAs($admin)
            ->post(
                route('products.store'),
                $this->productData($category, [
                    'sku' => 'DUPLICATE-SKU',
                ])
            )
            ->assertSessionHasErrors('sku');
    }

    public function test_product_must_belong_to_existing_category(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(
                route('products.store'),
                $this->productData($category, [
                    'category_id' => 999999,
                ])
            )
            ->assertSessionHasErrors('category_id');
    }

    public function test_sell_price_cannot_be_below_cost_price(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(
                route('products.store'),
                $this->productData($category, [
                    'cost_price' => '100.00',
                    'sell_price' => '99.99',
                ])
            )
            ->assertSessionHasErrors('sell_price');
    }

    public function test_quantity_cannot_be_submitted_from_product_form(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(
                route('products.store'),
                $this->productData($category, [
                    'quantity' => 999,
                ])
            )
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseMissing('products', [
            'name' => 'Wireless Mouse',
        ]);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->actingAs($admin)
            ->put(
                route('products.update', $product),

                $this->productData($category, [
                    'sku' => $product->sku,
                    'name' => 'Updated Product',
                    'cost_price' => '200.00',
                    'sell_price' => '250.00',
                ])
            )
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'cost_price' => 20000,
            'sell_price' => 25000,
        ]);
    }

    public function test_cashier_cannot_update_product(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $product = Product::factory()->create();

        $this->actingAs($cashier)
            ->put(
                route('products.update', $product),

                $this->productData($product->category, [
                    'sku' => $product->sku,
                    'name' => 'Unauthorized Update',
                ])
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'name' => 'Unauthorized Update',
        ]);
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $product = Product::factory()->create();

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    }

    public function test_cashier_cannot_delete_product(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $product = Product::factory()->create();

        $this->actingAs($cashier)
            ->delete(route('products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    }

    public function test_products_can_be_searched_by_name(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Product::factory()->create([
            'name' => 'Wireless Keyboard',
        ]);

        Product::factory()->create([
            'name' => 'Gaming Mouse',
        ]);

        $this->actingAs($admin)
            ->get(route('products.index', [
                'search' => 'Keyboard',
            ]))
            ->assertOk()
            ->assertSee('Wireless Keyboard')
            ->assertDontSee('Gaming Mouse');
    }

    public function test_products_can_be_searched_by_sku(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Product::factory()->create([
            'name' => 'Found Product',
            'sku' => 'SPECIAL-SKU-123',
        ]);

        Product::factory()->create([
            'name' => 'Hidden Product',
            'sku' => 'OTHER-SKU-456',
        ]);

        $this->actingAs($admin)
            ->get(route('products.index', [
                'search' => 'SPECIAL-SKU',
            ]))
            ->assertOk()
            ->assertSee('Found Product')
            ->assertDontSee('Hidden Product');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $electronics = Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $accessories = Category::factory()->create([
            'name' => 'Accessories',
        ]);

        Product::factory()->create([
            'name' => 'Laptop Product',
            'category_id' => $electronics->id,
        ]);

        Product::factory()->create([
            'name' => 'Cable Product',
            'category_id' => $accessories->id,
        ]);

        $this->actingAs($admin)
            ->get(route('products.index', [
                'category_id' => $electronics->id,
            ]))
            ->assertOk()
            ->assertSee('Laptop Product')
            ->assertDontSee('Cable Product');
    }

    public function test_products_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Product::factory()->create([
            'name' => 'Enabled Product',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name' => 'Disabled Product',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('products.index', [
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSee('Enabled Product')
            ->assertDontSee('Disabled Product');
    }

    public function test_invalid_sort_column_does_not_break_products_page(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Product::factory()->create();

        $this->actingAs($admin)
            ->get(route('products.index', [
                'sort' => 'unauthorized_column',
            ]))
            ->assertOk();
    }

    public function test_product_image_is_stored_using_public_disk(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        $image = UploadedFile::fake()
            ->image('product.jpg');

        $this->actingAs($admin)
            ->post(
                route('products.store'),

                $this->productData($category, [
                    'image' => $image,
                ])
            )
            ->assertRedirect(route('products.index'));

        $product = Product::query()
            ->where('name', 'Wireless Mouse')
            ->firstOrFail();

        $this->assertNotNull(
            $product->image_path
        );

        $this->assertFileExists(
            Storage::disk('public')->path($product->image_path)
        );
    }

    private function productData(
        Category $category,
        array $overrides = []
    ): array {
        return array_merge(
            [
                'category_id' => $category->id,
                'sku' => '',
                'name' => 'Wireless Mouse',
                'description' => 'Wireless mouse description',
                'cost_price' => '100.50',
                'sell_price' => '150.75',
                'reorder_level' => 5,
                'is_active' => true,
            ],

            $overrides
        );
    }
}
