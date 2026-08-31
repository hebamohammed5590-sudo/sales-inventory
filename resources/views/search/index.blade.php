<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Search Results') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <form
                method="GET"
                action="{{ route('search.index') }}"
                class="flex gap-3"
            >
                <x-text-input
                    name="q"
                    type="search"
                    class="block w-full"
                    :value="$term"
                    :placeholder="__('Search products, customers, suppliers, invoices...')"
                    required
                    minlength="2"
                    maxlength="100"
                />

                <x-primary-button>
                    {{ __('Search') }}
                </x-primary-button>
            </form>

            @if (
                $products->isEmpty()
                && $customers->isEmpty()
                && $suppliers->isEmpty()
                && $invoices->isEmpty()
            )
                <div class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">
                    {{ __('No results found.') }}
                </div>
            @endif

            @if ($products->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800">
                        {{ __('Products') }}
                    </h3>

                    <div class="space-y-3">
                        @foreach ($products as $product)
                            <a
                                href="{{ route('products.show', $product) }}"
                                class="block rounded-md border border-gray-200 p-4 hover:bg-gray-50"
                            >
                                <p class="font-medium text-gray-900">
                                    {{ $product->name }}
                                </p>

                                <p class="text-sm text-gray-500">
                                    {{ __('SKU') }}: {{ $product->sku }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($customers->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800">
                        {{ __('Customers') }}
                    </h3>

                    <div class="space-y-3">
                        @foreach ($customers as $customer)
                            <a
                                href="{{ route('customers.show', $customer) }}"
                                class="block rounded-md border border-gray-200 p-4 hover:bg-gray-50"
                            >
                                <p class="font-medium text-gray-900">
                                    {{ $customer->name }}
                                </p>

                                <p class="text-sm text-gray-500">
                                    {{ __('Phone') }}: {{ $customer->phone }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($suppliers->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800">
                        {{ __('Suppliers') }}
                    </h3>

                    <div class="space-y-3">
                        @foreach ($suppliers as $supplier)
                            <a
                                href="{{ route('suppliers.show', $supplier) }}"
                                class="block rounded-md border border-gray-200 p-4 hover:bg-gray-50"
                            >
                                <p class="font-medium text-gray-900">
                                    {{ $supplier->name }}
                                </p>

                                <p class="text-sm text-gray-500">
                                    {{ __('Phone') }}: {{ $supplier->phone }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($invoices->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800">
                        {{ __('Invoices') }}
                    </h3>

                    <div class="space-y-3">
                        @foreach ($invoices as $invoice)
                            <a
                                href="{{ route('invoices.show', [
                                    'type' => $invoice->type->value,
                                    'invoice' => $invoice,
                                ]) }}"
                                class="block rounded-md border border-gray-200 p-4 hover:bg-gray-50"
                            >
                                <p class="font-medium text-gray-900">
                                    {{ $invoice->invoice_number }}
                                </p>

                                <p class="text-sm text-gray-500">
                                    {{ __('Date') }}:
                                    {{ $invoice->invoice_date->format('Y-m-d') }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>