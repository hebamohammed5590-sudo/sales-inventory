<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                {{ __('Profit Report') }}
            </h2>

            <a
                href="{{ route('reports.index') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ __('Back to Reports') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
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
                            class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Revenue') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ money($report['revenue']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Cost') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ money($report['cost']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Profit') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-green-700 dark:text-green-400">
                        {{ money($report['profit']) }}
                        {{ $currency }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Profit by Product') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Product') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('SKU') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Quantity Sold') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Revenue') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Cost') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Profit') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($report['products'] as $product)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $product['product_name'] }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $product['sku'] }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $product['quantity'] }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ money($product['revenue']) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ money($product['cost']) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-green-700 dark:text-green-400">
                                        {{ money($product['profit']) }}
                                        {{ $currency }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
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