<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\CsvExportService;
use App\Services\ProductCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $allowedSorts = [
            'name',
            'sku',
            'cost_price',
            'sell_price',
            'quantity',
            'created_at',
        ];

        $sort = in_array(
            $request->string('sort')->toString(),
            $allowedSorts,
            true
        )
            ? $request->string('sort')->toString()
            : 'created_at';

        $direction = $request->string('direction')->toString()
            === 'asc'
            ? 'asc'
            : 'desc';

        $products = Product::query()
            ->with('category')

            ->when(
                $request->filled('search'),

                function ($query) use ($request): void {
                    $search = $request
                        ->string('search')
                        ->toString();

                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                        }
                    );
                }
            )

            ->when(
                $request->filled('category_id'),

                fn ($query) => $query->where(
                    'category_id',
                    $request->integer('category_id')
                )
            )

            ->when(
                $request->filled('status'),

                fn ($query) => $query->where(
                    'is_active',
                    $request->string('status')->toString() === 'active'
                )
            )

            ->orderBy($sort, $direction)

            ->paginate(15)

            ->withQueryString();

        $categories = Category::query()
            ->orderBy('name')
            ->get();

        return view(
            'products.index',
            compact(
                'products',
                'categories',
                'sort',
                'direction'
            )
        );
    }

    public function export(
        Request $request,
        CsvExportService $csvExportService
    ): StreamedResponse {
        Gate::authorize(
            'viewAny',
            Product::class
        );

        $allowedSorts = [
            'name',
            'sku',
            'cost_price',
            'sell_price',
            'quantity',
            'created_at',
        ];

        $sort = in_array(
            $request->string('sort')->toString(),
            $allowedSorts,
            true
        )
            ? $request->string('sort')->toString()
            : 'created_at';

        $direction = $request->string('direction')->toString() === 'asc'
            ? 'asc'
            : 'desc';

        $products = Product::query()
            ->with('category')

            ->when(
                $request->filled('search'),

                function ($query) use ($request): void {
                    $search = $request
                        ->string('search')
                        ->toString();

                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'sku',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )

            ->when(
                $request->filled('category_id'),

                fn ($query) => $query->where(
                    'category_id',
                    $request->integer('category_id')
                )
            )

            ->when(
                $request->filled('status'),

                fn ($query) => $query->where(
                    'is_active',
                    $request->string('status')->toString() === 'active'
                )
            )

            ->orderBy(
                $sort,
                $direction
            )

            ->get()

            ->map(
                fn (Product $product): array => [
                    $product->sku,

                    $product->name,

                    $product->category?->name ?? '',

                    (string) $product->cost_price,

                    (string) $product->sell_price,

                    $product->quantity,

                    $product->reorder_level,

                    $product->is_active ? 'Active' : 'Inactive',
                ]
            );

        return $csvExportService->export(
            'products.csv',

            [
                'SKU',
                'Name',
                'Category',
                'Cost Price',
                'Selling Price',
                'Quantity',
                'Reorder Level',
                'Status',
            ],

            $products
        );
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'products.create',
            compact('categories')
        );
    }

    public function store(
        StoreProductRequest $request
    ): RedirectResponse {
        $data = $request->validated();

        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        Product::create($data);

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                __('Product created successfully.')
            );
    }

    public function show(Product $product): View
    {
        Gate::authorize('view', $product);

        $product->load('category');

        return view(
            'products.show',
            compact('product')
        );
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        $categories = Category::query()
            ->where('is_active', true)
            ->orWhere('id', $product->category_id)
            ->orderBy('name')
            ->get();

        return view(
            'products.edit',
            compact('product', 'categories')
        );
    }

    public function update(
        UpdateProductRequest $request,
        Product $product
    ): RedirectResponse {
        $data = $request->validated();

        unset($data['image']);

        $oldImagePath = $product->image_path;

        if ($request->hasFile('image')) {
            $data['image_path'] = $request
                ->file('image')
                ->store('products', 'public');
        }

        $product->update($data);

        if (
            isset($data['image_path'])
            && $oldImagePath
        ) {
            Storage::disk('public')->delete(
                $oldImagePath
            );
        }

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                __('Product updated successfully.')
            );
    }

    public function destroy(
        Product $product
    ): RedirectResponse {
        Gate::authorize('delete', $product);

        $imagePath = $product->image_path;

        $product->delete();

        if ($imagePath) {
            Storage::disk('public')->delete(
                $imagePath
            );
        }

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                __('Product deleted successfully.')
            );
    }

    public function import(
        ImportProductsRequest $request,
        ProductCsvImportService $importService
    ): RedirectResponse {
        $result = $importService->import(
            $request->file('file')
        );

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                sprintf(
                    'CSV imported successfully: %d created, %d updated.',
                    $result['created'],
                    $result['updated']
                )
            );
    }

    public function downloadImportSample(): StreamedResponse
    {
        Gate::authorize(
            'create',
            Product::class
        );

        return response()->streamDownload(
            function (): void {
                $output = fopen(
                    'php://output',
                    'wb'
                );

                fwrite(
                    $output,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $output,
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

                fputcsv(
                    $output,
                    [
                        'PRD-SAMPLE-001',
                        'Sample Product',
                        'Electronics',
                        '100.00',
                        '125.00',
                        '5',
                        'true',
                    ]
                );

                fclose(
                    $output
                );
            },
            'products-import-sample.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }
}
