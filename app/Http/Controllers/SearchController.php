<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $request->merge([
            'q' => trim((string) $request->query('q')),
        ]);

        $validated = $request->validate([
            'q' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
        ]);

        $term = $validated['q'];
        $user = $request->user();

        $products = collect();

        if (
            Gate::forUser($user)->allows(
                'viewAny',
                Product::class
            )
        ) {
            $products = Product::query()
                ->where(
                    function ($query) use ($term): void {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$term}%"
                            )
                            ->orWhere(
                                'sku',
                                'like',
                                "%{$term}%"
                            );
                    }
                )
                ->orderBy('name')
                ->limit(5)
                ->get([
                    'id',
                    'name',
                    'sku',
                ]);
        }

        $customers = collect();

        if (
            Gate::forUser($user)->allows(
                'viewAny',
                Customer::class
            )
        ) {
            $customers = Customer::query()
                ->where(
                    function ($query) use ($term): void {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$term}%"
                            )
                            ->orWhere(
                                'phone',
                                'like',
                                "%{$term}%"
                            );
                    }
                )
                ->orderBy('name')
                ->limit(5)
                ->get([
                    'id',
                    'name',
                    'phone',
                ]);
        }

        $suppliers = collect();

        if (
            Gate::forUser($user)->allows(
                'viewAny',
                Supplier::class
            )
        ) {
            $suppliers = Supplier::query()
                ->where(
                    function ($query) use ($term): void {
                        $query
                            ->where(
                                'name',
                                'like',
                                "%{$term}%"
                            )
                            ->orWhere(
                                'phone',
                                'like',
                                "%{$term}%"
                            );
                    }
                )
                ->orderBy('name')
                ->limit(5)
                ->get([
                    'id',
                    'name',
                    'phone',
                ]);
        }

        $invoices = collect();

        if (
            Gate::forUser($user)->allows(
                'viewAny',
                Invoice::class
            )
        ) {
            $invoices = Invoice::query()
                ->where(
                    'invoice_number',
                    'like',
                    "%{$term}%"
                )
                ->when(
                    $user->role === Role::Cashier,
                    function ($query): void {
                        $query->where(
                            'type',
                            InvoiceType::Sale->value
                        );
                    }
                )
                ->latest('invoice_date')
                ->limit(5)
                ->get([
                    'id',
                    'invoice_number',
                    'type',
                    'invoice_date',
                    'status',
                ]);
        }

        return view(
            'search.index',
            compact(
                'term',
                'products',
                'customers',
                'suppliers',
                'invoices'
            )
        );
    }
}
