<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CsvExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Customer::class);

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

        $customers = Customer::query()
            ->search(
                $request->string('search')->toString()
            )
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view(
            'customers.index',
            compact(
                'customers',
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
            Customer::class
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

        $customers = Customer::query()

            ->search(
                $request->string('search')->toString()
            )

            ->orderBy(
                $sort,
                $direction
            )

            ->get()

            ->map(
                fn (Customer $customer): array => [
                    $customer->name,

                    $customer->phone,

                    $customer->email ?? '',

                    $customer->address ?? '',

                    $customer->notes ?? '',

                    $customer->created_at?->format(
                        'Y-m-d H:i:s'
                    ) ?? '',
                ]
            );

        return $csvExportService->export(
            'customers.csv',

            [
                'Name',
                'Phone',
                'Email',
                'Address',
                'Notes',
                'Created At',
            ],

            $customers
        );
    }

    public function create(): View
    {
        Gate::authorize('create', Customer::class);

        return view('customers.create');
    }

    public function store(
        StoreCustomerRequest $request
    ): RedirectResponse {
        Customer::create(
            $request->validated()
        );

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                __('Customer created successfully.')
            );
    }

    public function show(Customer $customer): View
    {
        Gate::authorize('view', $customer);

        return view(
            'customers.show',
            compact('customer')
        );
    }

    public function edit(Customer $customer): View
    {
        Gate::authorize('update', $customer);

        return view(
            'customers.edit',
            compact('customer')
        );
    }

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer
    ): RedirectResponse {
        $customer->update(
            $request->validated()
        );

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                __('Customer updated successfully.')
            );
    }

    public function destroy(
        Customer $customer
    ): RedirectResponse {
        Gate::authorize('delete', $customer);

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with(
                'success',
                __('Customer deleted successfully.')
            );
    }
}
