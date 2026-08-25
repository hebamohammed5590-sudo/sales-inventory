<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                @if ($invoiceType === \App\Enums\InvoiceType::Sale)
                    Sales Invoices
                @else
                    Purchase Invoices
                @endif
            </h2>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route(
                        'invoices.export',
                        array_merge(
                            request()->query(),
                            [
                                'type' => $invoiceType->value,
                            ]
                        )
                    ) }}"
                    class="rounded-md border border-green-600 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-50"
                >
                    {{ __('Export CSV') }}
                </a>

                @can('create', [\App\Models\Invoice::class, $invoiceType])
                    <a
                        href="{{ route('invoices.create', ['type' => $invoiceType->value]) }}"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Add Invoice
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg bg-green-100 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <form
                    method="GET"
                    action="{{ route('invoices.index', ['type' => $invoiceType->value]) }}"
                    class="grid gap-4 p-6 md:grid-cols-3"
                >
                    <div>
                        <x-input-label
                            for="search"
                            value="Invoice Number"
                        />

                        <x-text-input
                            id="search"
                            name="search"
                            type="text"
                            class="mt-1 block w-full"
                            :value="request('search')"
                            placeholder="Search invoice number"
                        />
                    </div>

                    <div>
                        <x-input-label
                            for="status"
                            value="Status"
                        />

                        <select
                            id="status"
                            name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">
                                All statuses
                            </option>

                            @foreach ($statuses as $status)
                                <option
                                    value="{{ $status->value }}"
                                    @selected(request('status') === $status->value)
                                >
                                    {{ str($status->value)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            Filter
                        </x-primary-button>

                        <a
                            href="{{ route('invoices.index', ['type' => $invoiceType->value]) }}"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Invoice Number
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    @if ($invoiceType === \App\Enums\InvoiceType::Sale)
                                        Customer
                                    @else
                                        Supplier
                                    @endif
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Date
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Total
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Created By
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $invoice->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if ($invoiceType === \App\Enums\InvoiceType::Sale)
                                            {{ $invoice->customer?->name ?? '-' }}
                                        @else
                                            {{ $invoice->supplier?->name ?? '-' }}
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $invoice->invoice_date->format('Y-m-d') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $invoice->total }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @php
                                            $statusClasses = match ($invoice->status) {
                                                \App\Enums\InvoiceStatus::Draft
                                                    => 'bg-gray-100 text-gray-800',

                                                \App\Enums\InvoiceStatus::Confirmed
                                                    => 'bg-blue-100 text-blue-800',

                                                \App\Enums\InvoiceStatus::PartiallyPaid
                                                    => 'bg-yellow-100 text-yellow-800',

                                                \App\Enums\InvoiceStatus::Paid
                                                    => 'bg-green-100 text-green-800',

                                                \App\Enums\InvoiceStatus::Cancelled
                                                    => 'bg-red-100 text-red-800',
                                            };
                                        @endphp

                                        <span class="rounded-full px-3 py-1 {{ $statusClasses }}">
                                            {{ str($invoice->status->value)->replace('_', ' ')->title() }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $invoice->user->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            href="{{ route('invoices.show', [
                                                'type' => $invoiceType->value,
                                                'invoice' => $invoice,
                                            ]) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No invoices found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $invoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>