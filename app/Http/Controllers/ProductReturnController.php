<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\StoreProductReturnRequest;
use App\Models\Invoice;
use App\Models\ProductReturn;
use App\Services\ProductReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductReturnController extends Controller
{
    public function index(
        Request $request
    ): View {
        Gate::authorize(
            'viewAny',
            ProductReturn::class
        );

        $query = ProductReturn::query()
            ->with([
                'invoice.customer',
                'user',
            ]);

        if ($request->filled('search')) {
            $search = $request
                ->string('search')
                ->toString();

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'return_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'invoice',
                        function ($query) use ($search) {
                            $query->where(
                                'invoice_number',
                                'like',
                                "%{$search}%"
                            );
                        }
                    )
                    ->orWhereHas(
                        'invoice.customer',
                        function ($query) use ($search) {
                            $query->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        $productReturns = $query
            ->latest('return_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'product-returns.index',
            compact('productReturns')
        );
    }

    public function create(
        Invoice $invoice
    ): View {
        Gate::authorize(
            'create',
            ProductReturn::class
        );

        $this->ensureInvoiceCanShowReturnForm(
            $invoice
        );

        $invoice->load([
            'customer',
            'items' => function ($query) {
                $query
                    ->with('product')
                    ->withSum(
                        'productReturnItems as returned_quantity',
                        'quantity'
                    );
            },
        ]);

        $hasReturnableItems = $invoice->items
            ->contains(
                fn ($item): bool => $item->quantity
                    > (int) ($item->returned_quantity ?? 0)
            );

        return view(
            'product-returns.create',
            compact(
                'invoice',
                'hasReturnableItems'
            )
        );
    }

    public function store(
        StoreProductReturnRequest $request,
        Invoice $invoice,
        ProductReturnService $productReturnService
    ): RedirectResponse {
        Gate::authorize(
            'create',
            ProductReturn::class
        );

        abort_unless(
            $invoice->isSale(),
            404
        );

        $productReturn = $productReturnService->create(
            invoice: $invoice,
            user: $request->user(),
            data: $request->validated()
        );

        return redirect()
            ->route(
                'product-returns.show',
                $productReturn
            )
            ->with(
                'success',
                __('Product return created successfully.')
            );
    }

    public function show(
        ProductReturn $productReturn
    ): View {
        Gate::authorize(
            'view',
            $productReturn
        );

        $productReturn->load([
            'invoice.customer',
            'user',
            'items.product',
            'items.invoiceItem',
            'stockMovements.user',
        ]);

        return view(
            'product-returns.show',
            compact('productReturn')
        );
    }

    private function ensureInvoiceCanShowReturnForm(
        Invoice $invoice
    ): void {
        abort_unless(
            $invoice->isSale(),
            404
        );

        abort_unless(
            in_array(
                $invoice->status,
                [
                    InvoiceStatus::Confirmed,
                    InvoiceStatus::PartiallyPaid,
                    InvoiceStatus::Paid,
                ],
                true
            ),
            404
        );
    }
}
