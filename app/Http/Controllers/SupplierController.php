<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\CsvExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Supplier::class);

        $allowedSorts = [
            'name',
            'phone',
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

        $suppliers = Supplier::query()
            ->search(
                $request->string('search')->toString()
            )
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view(
            'suppliers.index',
            compact(
                'suppliers',
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
            Supplier::class
        );

        $allowedSorts = [
            'name',
            'phone',
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

        $suppliers = Supplier::query()

            ->search(
                $request->string('search')->toString()
            )

            ->orderBy(
                $sort,
                $direction
            )

            ->get()

            ->map(
                fn (Supplier $supplier): array => [
                    $supplier->name,

                    $supplier->phone,

                    $supplier->email ?? '',

                    $supplier->address ?? '',

                    $supplier->notes ?? '',

                    $supplier->created_at?->format(
                        'Y-m-d H:i:s'
                    ) ?? '',
                ]
            );

        return $csvExportService->export(
            'suppliers.csv',

            [
                'Name',
                'Phone',
                'Email',
                'Address',
                'Notes',
                'Created At',
            ],

            $suppliers
        );
    }

    public function create(): View
    {
        Gate::authorize('create', Supplier::class);

        return view('suppliers.create');
    }

    public function store(
        StoreSupplierRequest $request
    ): RedirectResponse {
        Supplier::create(
            $request->validated()
        );

        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                __('Supplier created successfully.')
            );
    }

    public function show(Supplier $supplier): View
    {
        Gate::authorize('view', $supplier);

        return view(
            'suppliers.show',
            compact('supplier')
        );
    }

    public function edit(Supplier $supplier): View
    {
        Gate::authorize('update', $supplier);

        return view(
            'suppliers.edit',
            compact('supplier')
        );
    }

    public function update(
        UpdateSupplierRequest $request,
        Supplier $supplier
    ): RedirectResponse {
        $supplier->update(
            $request->validated()
        );

        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                __('Supplier updated successfully.')
            );
    }

    public function destroy(
        Supplier $supplier
    ): RedirectResponse {
        Gate::authorize('delete', $supplier);

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with(
                'success',
                __('Supplier deleted successfully.')
            );
    }
}
