<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $cashier;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::Admin,
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create([
            'role' => Role::Manager,
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => Role::Cashier,
            'is_active' => true,
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Electronics',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_import_new_products(): void
    {
        $file = $this->csvFile([
            [
                'PRD-CSV-001',
                'Imported Laptop',
                'Electronics',
                '100.00',
                '150.00',
                '5',
                'true',
            ],
            [
                'PRD-CSV-002',
                'Imported Mouse',
                'Electronics',
                '20.00',
                '35.00',
                '3',
                'true',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertRedirect(
            route('products.index')
        );

        $response->assertSessionHas(
            'success'
        );

        $this->assertDatabaseHas('products', [
            'sku' => 'PRD-CSV-001',
            'name' => 'Imported Laptop',
            'category_id' => $this->category->id,
            'quantity' => 0,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'PRD-CSV-002',
            'name' => 'Imported Mouse',
        ]);
    }

    public function test_manager_can_import_products(): void
    {
        $file = $this->csvFile([
            [
                'PRD-CSV-003',
                'Manager Product',
                'Electronics',
                '50.00',
                '80.00',
                '4',
                'true',
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertRedirect(
            route('products.index')
        );

        $this->assertDatabaseHas('products', [
            'sku' => 'PRD-CSV-003',
            'name' => 'Manager Product',
        ]);
    }

    public function test_cashier_cannot_import_products(): void
    {
        $file = $this->csvFile([
            [
                'PRD-CSV-004',
                'Unauthorized Product',
                'Electronics',
                '50.00',
                '80.00',
                '4',
                'true',
            ],
        ]);

        $response = $this->actingAs($this->cashier)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseMissing('products', [
            'sku' => 'PRD-CSV-004',
        ]);
    }

    public function test_guest_cannot_import_products(): void
    {
        $file = $this->csvFile([
            [
                'PRD-CSV-005',
                'Guest Product',
                'Electronics',
                '50.00',
                '80.00',
                '4',
                'true',
            ],
        ]);

        $response = $this->post(
            route('products.import'),
            [
                'file' => $file,
            ]
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_existing_sku_updates_product(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'sku' => 'PRD-EXISTING-001',
            'name' => 'Old Product Name',
            'cost_price' => '40.00',
            'sell_price' => '60.00',
            'quantity' => 12,
            'reorder_level' => 3,
            'is_active' => true,
        ]);

        $file = $this->csvFile([
            [
                'PRD-EXISTING-001',
                'Updated Product Name',
                'Electronics',
                '70.00',
                '95.00',
                '8',
                'false',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertRedirect(
            route('products.index')
        );

        $product->refresh();

        $this->assertSame(
            'Updated Product Name',
            $product->name
        );

        $this->assertSame(
            '70.00',
            $product->cost_price
        );

        $this->assertSame(
            '95.00',
            $product->sell_price
        );

        $this->assertSame(
            8,
            $product->reorder_level
        );

        $this->assertFalse(
            $product->is_active
        );

        $this->assertSame(
            12,
            $product->quantity
        );
    }

    public function test_import_requires_a_file(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                []
            );

        $response->assertSessionHasErrors(
            'file'
        );
    }

    public function test_import_rejects_non_csv_file(): void
    {
        $file = UploadedFile::fake()->create(
            'products.pdf',
            10,
            'application/pdf'
        );

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );
    }

    public function test_import_rejects_empty_csv(): void
    {
        $file = UploadedFile::fake()
            ->createWithContent(
                'products.csv',
                ''
            );

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );
    }

    public function test_import_rejects_missing_required_columns(): void
    {
        $file = UploadedFile::fake()
            ->createWithContent(
                'products.csv',
                "sku,name\nPRD-001,Incomplete Product\n"
            );

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );

        $this->assertDatabaseMissing('products', [
            'sku' => 'PRD-001',
        ]);
    }

    public function test_import_rejects_quantity_column(): void
    {
        $contents = implode(
            ',',
            [
                'sku',
                'name',
                'category',
                'cost_price',
                'sell_price',
                'reorder_level',
                'is_active',
                'quantity',
            ]
        );

        $contents .= "\n";

        $contents .= implode(
            ',',
            [
                'PRD-CSV-QTY',
                'Invalid Quantity Product',
                'Electronics',
                '100.00',
                '150.00',
                '5',
                'true',
                '99',
            ]
        );

        $file = UploadedFile::fake()
            ->createWithContent(
                'products.csv',
                $contents
            );

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );

        $this->assertDatabaseMissing('products', [
            'sku' => 'PRD-CSV-QTY',
        ]);
    }

    public function test_import_rejects_unknown_category(): void
    {
        $file = $this->csvFile([
            [
                'PRD-CSV-006',
                'Unknown Category Product',
                'Missing Category',
                '50.00',
                '75.00',
                '5',
                'true',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );

        $this->assertDatabaseMissing('products', [
            'sku' => 'PRD-CSV-006',
        ]);
    }

    public function test_import_rejects_selling_price_below_cost(): void
    {
        $file = $this->csvFile([
            [
                'PRD-CSV-007',
                'Invalid Price Product',
                'Electronics',
                '100.00',
                '80.00',
                '5',
                'true',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );

        $this->assertDatabaseMissing('products', [
            'sku' => 'PRD-CSV-007',
        ]);
    }

    public function test_import_rejects_duplicate_skus_in_same_file(): void
    {
        $file = $this->csvFile([
            [
                'PRD-DUPLICATE',
                'First Product',
                'Electronics',
                '50.00',
                '75.00',
                '5',
                'true',
            ],
            [
                'PRD-DUPLICATE',
                'Second Product',
                'Electronics',
                '60.00',
                '85.00',
                '5',
                'true',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );

        $this->assertDatabaseMissing('products', [
            'sku' => 'PRD-DUPLICATE',
        ]);
    }

    public function test_invalid_row_prevents_partial_import(): void
    {
        $file = $this->csvFile([
            [
                'PRD-VALID-001',
                'Valid Product',
                'Electronics',
                '50.00',
                '75.00',
                '5',
                'true',
            ],
            [
                'PRD-INVALID-001',
                'Invalid Product',
                'Missing Category',
                '50.00',
                '75.00',
                '5',
                'true',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );

        $this->assertDatabaseMissing('products', [
            'sku' => 'PRD-VALID-001',
        ]);

        $this->assertDatabaseMissing('products', [
            'sku' => 'PRD-INVALID-001',
        ]);
    }

    public function test_import_validation_error_includes_row_number(): void
    {
        $file = $this->csvFile([
            [
                'PRD-ROW-001',
                'Invalid Price Product',
                'Electronics',
                '100.00',
                '70.00',
                '5',
                'true',
            ],
        ]);

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertSessionHasErrors(
            'file'
        );

        $errors = session('errors')->get(
            'file'
        );

        $this->assertStringContainsString(
            'Row 2',
            $errors[0]
        );
    }

    public function test_import_accepts_csv_with_utf8_bom(): void
    {
        $contents = "\xEF\xBB\xBF";

        $contents .= implode(
            ',',
            [
                'sku',
                'name',
                'category',
                'cost_price',
                'sell_price',
                'reorder_level',
                'is_active',
            ]
        );

        $contents .= "\n";

        $contents .= implode(
            ',',
            [
                'PRD-BOM-001',
                'Arabic Compatible Product',
                'Electronics',
                '100.00',
                '150.00',
                '5',
                'true',
            ]
        );

        $file = UploadedFile::fake()
            ->createWithContent(
                'products.csv',
                $contents
            );

        $response = $this->actingAs($this->admin)
            ->post(
                route('products.import'),
                [
                    'file' => $file,
                ]
            );

        $response->assertRedirect(
            route('products.index')
        );

        $this->assertDatabaseHas('products', [
            'sku' => 'PRD-BOM-001',
        ]);
    }

    public function test_admin_can_download_sample_csv(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(
                route('products.import.sample')
            );

        $response->assertOk();

        $response->assertHeader(
            'content-type',
            'text/csv; charset=UTF-8'
        );

        $contents = $response->streamedContent();

        $this->assertStringContainsString(
            'sku,name,category,cost_price,sell_price,reorder_level,is_active',
            $contents
        );

        $this->assertStringContainsString(
            'PRD-SAMPLE-001',
            $contents
        );
    }

    public function test_cashier_cannot_download_sample_csv(): void
    {
        $response = $this->actingAs($this->cashier)
            ->get(
                route('products.import.sample')
            );

        $response->assertForbidden();
    }

    public function test_products_page_shows_import_form(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(
                route('products.index')
            );

        $response->assertOk();

        $response->assertSee(
            'Import CSV'
        );

        $response->assertSee(
            'Download Sample CSV'
        );
    }

    private function csvFile(
        array $rows
    ): UploadedFile {
        $headers = [
            'sku',
            'name',
            'category',
            'cost_price',
            'sell_price',
            'reorder_level',
            'is_active',
        ];

        $contents = implode(
            ',',
            $headers
        );

        foreach ($rows as $row) {
            $contents .= "\n";

            $contents .= implode(
                ',',
                $row
            );
        }

        return UploadedFile::fake()
            ->createWithContent(
                'products.csv',
                $contents
            );
    }
}
