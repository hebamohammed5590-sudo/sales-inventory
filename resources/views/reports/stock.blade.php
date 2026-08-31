<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Stock Report') }}
            </h2>

            <a
                href="{{ route('reports.index') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
            >
                {{ __('Back to Reports') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Products') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ $report['products_count'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Total Quantity') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ $report['total_quantity'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Low Stock Products') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-red-700">
                        {{ $report['low_stock_count'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Cost Value') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ money($report['total_cost_value']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Selling Value') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ money($report['total_selling_value']) }}
                        {{ $currency }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    {{ __('Inventory Details') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Product') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('SKU') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Category') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Quantity') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Reorder Level') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Cost Price') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Selling Price') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Status') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($report['products'] as $product)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $product->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->sku }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->category?->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->quantity }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->reorder_level }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ money($product->getRawOriginal('cost_price')) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ money($product->getRawOriginal('sell_price')) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @if ($product->quantity <= $product->reorder_level)
                                            <span class="rounded-full bg-red-100 px-3 py-1 text-red-800">
                                                {{ __('Low Stock') }}
                                            </span>
                                        @else
                                            <span class="rounded-full bg-green-100 px-3 py-1 text-green-800">
                                                {{ __('In Stock') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="8"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        {{ __('No products found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>