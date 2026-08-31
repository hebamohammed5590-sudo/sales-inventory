<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Reports') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <a
                    href="{{ route('reports.sales') }}"
                    class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ __('Sales Report') }}
                    </h3>

                    <p class="mt-3 text-sm text-gray-600">
                        {{ __('Review sales invoices, totals, and daily sales.') }}
                    </p>

                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                        {{ __('Open Report') }}
                    </span>
                </a>

                <a
                    href="{{ route('reports.purchases') }}"
                    class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ __('Purchases Report') }}
                    </h3>

                    <p class="mt-3 text-sm text-gray-600">
                        {{ __('Review purchase invoices, totals, and daily purchases.') }}
                    </p>

                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                        {{ __('Open Report') }}
                    </span>
                </a>

                <a
                    href="{{ route('reports.profit') }}"
                    class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ __('Profit Report') }}
                    </h3>

                    <p class="mt-3 text-sm text-gray-600">
                        {{ __('Compare sales revenue, product costs, and profit.') }}
                    </p>

                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                        {{ __('Open Report') }}
                    </span>
                </a>

                <a
                    href="{{ route('reports.stock') }}"
                    class="rounded-lg bg-white p-6 shadow-sm transition hover:shadow-md"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ __('Stock Report') }}
                    </h3>

                    <p class="mt-3 text-sm text-gray-600">
                        {{ __('Review inventory quantities, values, and low-stock products.') }}
                    </p>

                    <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                        {{ __('Open Report') }}
                    </span>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>