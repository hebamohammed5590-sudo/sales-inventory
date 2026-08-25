<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    public function index(): View
    {
        Gate::authorize(
            'viewAny',
            StockAdjustment::class
        );

        $adjustments = StockAdjustment::query()
            ->with([
                'product',
                'user',
            ])
            ->latest()
            ->paginate(15);

        return view(
            'stock-adjustments.index',
            compact('adjustments')
        );
    }

    public function create(): View
    {
        Gate::authorize(
            'create',
            StockAdjustment::class
        );

        $products = Product::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'sku',
                'quantity',
            ]);

        return view(
            'stock-adjustments.create',
            compact('products')
        );
    }

    public function store(
        StoreStockAdjustmentRequest $request,
        StockService $stockService
    ): RedirectResponse {
        $validated = $request->validated();

        $product = Product::query()->findOrFail(
            $validated['product_id']
        );

        $stockService->adjust(
            product: $product,
            user: $request->user(),
            quantityChange: (int) $validated['quantity_change'],
            notes: $validated['notes']
        );

        return redirect()
            ->route('stock-adjustments.index')
            ->with(
                'success',
                'Stock adjusted successfully.'
            );
    }
}
