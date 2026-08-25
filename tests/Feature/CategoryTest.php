<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    public function test_category_with_products_cannot_be_deleted(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('categories.destroy', $category));

        $response->assertRedirect(
            route('categories.index')
        );

        $response->assertSessionHas(
            'error',
            'Cannot delete a category that has products.'
        );

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }

    use RefreshDatabase;

    public function test_admin_can_view_categories(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->get(route('categories.index'))
            ->assertOk();
    }

    public function test_manager_can_view_categories(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->get(route('categories.index'))
            ->assertOk();
    }

    public function test_cashier_cannot_view_categories(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($cashier)
            ->get(route('categories.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_view_categories(): void
    {
        $this->get(route('categories.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_category(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => 'Electronics',
                'description' => 'Electronic devices',
                'is_active' => true,
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Electronics',
            'description' => 'Electronic devices',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_create_category(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $this->actingAs($manager)
            ->post(route('categories.store'), [
                'name' => 'Accessories',
                'description' => 'Product accessories',
                'is_active' => true,
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Accessories',
        ]);
    }

    public function test_cashier_cannot_create_category(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $this->actingAs($cashier)
            ->post(route('categories.store'), [
                'name' => 'Unauthorized Category',
                'description' => 'Should not be created',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', [
            'name' => 'Unauthorized Category',
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $this->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => '',
                'description' => 'Missing category name',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_category_name_must_be_unique(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        Category::factory()->create([
            'name' => 'Electronics',
        ]);

        $this->actingAs($admin)
            ->post(route('categories.store'), [
                'name' => 'Electronics',
                'description' => 'Duplicate category',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_admin_can_update_category(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create([
            'name' => 'Old Name',
        ]);

        $this->actingAs($admin)
            ->put(route('categories.update', $category), [
                'name' => 'Updated Name',
                'description' => 'Updated description',
                'is_active' => true,
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ]);
    }

    public function test_manager_can_update_category(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($manager)
            ->put(route('categories.update', $category), [
                'name' => 'Manager Updated Category',
                'description' => 'Updated by manager',
                'is_active' => true,
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Manager Updated Category',
        ]);
    }

    public function test_cashier_cannot_update_category(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $category = Category::factory()->create([
            'name' => 'Original Name',
        ]);

        $this->actingAs($cashier)
            ->put(route('categories.update', $category), [
                'name' => 'Unauthorized Update',
                'description' => 'Should not change',
                'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Original Name',
        ]);
    }

    public function test_category_can_be_deactivated(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create([
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('categories.update', $category), [
                'name' => $category->name,
                'description' => $category->description,
                'is_active' => false,
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_empty_category(): void
    {
        $admin = User::factory()->create([
            'role' => Role::Admin,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_manager_can_delete_empty_category(): void
    {
        $manager = User::factory()->create([
            'role' => Role::Manager,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($manager)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_cashier_cannot_delete_category(): void
    {
        $cashier = User::factory()->create([
            'role' => Role::Cashier,
        ]);

        $category = Category::factory()->create();

        $this->actingAs($cashier)
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }
}
