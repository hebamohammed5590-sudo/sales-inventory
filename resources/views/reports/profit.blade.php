<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Profit Report') }}
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
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('reports.profit') }}"
                    class="grid gap-4 md:grid-cols-3"
                >
                    <div>
                        <x-input-label
                            for="from"
                            :value="__('From')"
                        />

                        <x-text-input
                            id="from"
                            name="from"
                            type="date"
                            class="mt-1 block w-full"
                            :value="$report['from']->format('Y-m-d')"
                        />
                    </div>

                    <div>
                        <x-input-label
                            for="to"
                            :value="__('To')"
                        />

                        <x-text-input
                            id="to"
                            name="to"
                            type="date"
                            class="mt-1 block w-full"
                            :value="$report['to']->format('Y-m-d')"
                        />
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            {{ __('Apply Filter') }}
                        </x-primary-button>

                        <a
                            href="{{ route('reports.profit') }}"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Revenue') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ money($report['revenue']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Cost') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ money($report['cost']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Profit') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-green-700">
                        {{ money($report['profit']) }}
                        {{ $currency }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    {{ __('Profit by Product') }}
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
                                    {{ __('Quantity Sold') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Revenue') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Cost') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Profit') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($report['products'] as $product)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $product['product_name'] }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product['sku'] }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product['quantity'] }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ money($product['revenue']) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ money($product['cost']) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-green-700">
                                        {{ money($product['profit']) }}
                                        {{ $currency }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        {{ __('No sales found for this period.') }}
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