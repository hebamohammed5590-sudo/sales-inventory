<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_global_search(): void
    {
        $response = $this->get(
            route('search.index', [
                'q' => 'test',
            ])
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_search_query_must_be_at_least_two_characters(): void
    {
        $admin = $this->createUser(Role::Admin);

        $response = $this
            ->actingAs($admin)
            ->get(
                route('search.index', [
                    'q' => 'a',
                ])
            );

        $response->assertSessionHasErrors('q');
    }

    public function test_search_query_is_trimmed_and_required(): void
    {
        $admin = $this->createUser(Role::Admin);

        $response = $this
            ->actingAs($admin)
            ->get(
                route('search.index', [
                    'q' => '   ',
                ])
            );

        $response->assertSessionHasErrors('q');
    }

    public function test_admin_and_manager_can_search_all_supported_resources(): void
    {
        $admin = $this->createUser(Role::Admin);
        $manager = $this->createUser(Role::Manager);

        [
            $customer,
            $supplier,
            $product,
            $sale,
            $purchase,
        ] = $this->createSearchableRecords(
            $admin,
            'GLOBALMATCH'
        );

        foreach ([$admin, $manager] as $user) {
            $response = $this
                ->actingAs($user)
                ->get(
                    route('search.index', [
                        'q' => 'GLOBALMATCH',
                    ])
                );

            $response->assertOk();

            $response->assertSeeText(
                $product->name
            );

            $response->assertSeeText(
                $customer->name
            );

            $response->assertSeeText(
                $supplier->name
            );

            $response->assertSeeText(
                $sale->invoice_number
            );

            $response->assertSeeText(
                $purchase->invoice_number
            );
        }
    }

    public function test_cashier_only_sees_authorized_search_results(): void
    {
        $admin = $this->createUser(Role::Admin);
        $cashier = $this->createUser(Role::Cashier);

        [
            $customer,
            $supplier,
            $product,
            $sale,
            $purchase,
        ] = $this->createSearchableRecords(
            $admin,
            'CASHIERMATCH'
        );

        $response = $this
            ->actingAs($cashier)
            ->get(
                route('search.index', [
                    'q' => 'CASHIERMATCH',
                ])
            );

        $response->assertOk();

        $response->assertSeeText(
            $customer->name
        );

        $response->assertSeeText(
            $sale->invoice_number
        );

        $response->assertDontSeeText(
            $product->name
        );

        $response->assertDontSeeText(
            $supplier->name
        );

        $response->assertDontSeeText(
            $purchase->invoice_number
        );
    }

    public function test_search_supports_product_sku_customer_phone_and_supplier_phone(): void
    {
        $admin = $this->createUser(Role::Admin);

        $product = Product::factory()->create([
            'name' => 'Special Search Product',
            'sku' => 'SKU-LOOKUP-777',
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Phone Search Customer',
            'phone' => '01012345678',
        ]);

        $supplier = Supplier::factory()->create([
            'name' => 'Phone Search Supplier',
            'phone' => '01187654321',
        ]);

        $this
            ->actingAs($admin)
            ->get(
                route('search.index', [
                    'q' => 'LOOKUP-777',
                ])
            )
            ->assertOk()
            ->assertSeeText(
                $product->name
            );

        $this
            ->actingAs($admin)
            ->get(
                route('search.index', [
                    'q' => '01012345678',
                ])
            )
            ->assertOk()
            ->assertSeeText(
                $customer->name
            );

        $this
            ->actingAs($admin)
            ->get(
                route('search.index', [
                    'q' => '01187654321',
                ])
            )
            ->assertOk()
            ->assertSeeText(
                $supplier->name
            );
    }

    public function test_search_supports_invoice_number(): void
    {
        $admin = $this->createUser(Role::Admin);

        [
            ,
            ,
            ,
            $sale,
        ] = $this->createSearchableRecords(
            $admin,
            'INVOICELOOKUP'
        );

        $response = $this
            ->actingAs($admin)
            ->get(
                route('search.index', [
                    'q' => 'INVOICELOOKUP-SALE',
                ])
            );

        $response->assertOk();

        $response->assertSeeText(
            $sale->invoice_number
        );
    }

    public function test_search_displays_no_results_message(): void
    {
        $admin = $this->createUser(Role::Admin);

        $response = $this
            ->actingAs($admin)
            ->get(
                route('search.index', [
                    'q' => 'NOTHING-SHOULD-MATCH-999',
                ])
            );

        $response->assertOk();

        $response->assertSeeText(
            'No results found.'
        );
    }

    public function test_search_limits_each_resource_to_five_results(): void
    {
        $admin = $this->createUser(Role::Admin);

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $suffix) {
            Product::factory()->create([
                'name' => "LIMITMATCH Product {$suffix}",
            ]);
        }

        $response = $this
            ->actingAs($admin)
            ->get(
                route('search.index', [
                    'q' => 'LIMITMATCH',
                ])
            );

        $response->assertOk();

        foreach (['A', 'B', 'C', 'D', 'E'] as $suffix) {
            $response->assertSeeText(
                "LIMITMATCH Product {$suffix}"
            );
        }

        $response->assertDontSeeText(
            'LIMITMATCH Product F'
        );
    }

    public function test_authenticated_navigation_contains_global_search(): void
    {
        $admin = $this->createUser(Role::Admin);

        $response = $this
            ->actingAs($admin)
            ->get(
                route('dashboard')
            );

        $response->assertOk();

        $response->assertSee(
            'placeholder="Global Search"',
            false
        );

        $response->assertSee(
            route('search.index')
        );
    }

    private function createSearchableRecords(
        User $actor,
        string $token
    ): array {
        $customer = Customer::factory()->create([
            'name' => "{$token} Customer",
        ]);

        $supplier = Supplier::factory()->create([
            'name' => "{$token} Supplier",
        ]);

        $product = Product::factory()->create([
            'name' => "{$token} Product",
            'quantity' => 20,
        ]);

        $invoiceService = app(
            InvoiceService::class
        );

        $sale = $invoiceService->create(
            InvoiceType::Sale,
            $actor,
            [
                'customer_id' => $customer->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ]
        );

        $purchase = $invoiceService->create(
            InvoiceType::Purchase,
            $actor,
            [
                'supplier_id' => $supplier->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
            ]
        );

        Invoice::query()
            ->whereKey($sale->id)
            ->update([
                'invoice_number' => "{$token}-SALE-001",
            ]);

        Invoice::query()
            ->whereKey($purchase->id)
            ->update([
                'invoice_number' => "{$token}-PURCHASE-001",
            ]);

        return [
            $customer,
            $supplier,
            $product,
            $sale->refresh(),
            $purchase->refresh(),
        ];
    }

    private function createUser(Role $role): User
    {
        return User::factory()->create([
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
