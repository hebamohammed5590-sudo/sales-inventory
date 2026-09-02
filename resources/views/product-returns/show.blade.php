<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    {{ __('Product Return') }}
                    {{ $productReturn->return_number }}
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a
                    href="{{ route('invoices.show', [
                        'type' => 'sale',
                        'invoice' => $productReturn->invoice,
                    ]) }}"
                    class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    {{ __('Original Invoice') }}
                </a>

                <a
                    href="{{ route('product-returns.index') }}"
                    class="font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                >
                    {{ __('Back to Product Returns') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg bg-green-100 p-4 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Return Details') }}
                </h3>

                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Return Number') }}
                        </p>

                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $productReturn->return_number }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Invoice Number') }}
                        </p>

                        <a
                            href="{{ route('invoices.show', [
                                'type' => 'sale',
                                'invoice' => $productReturn->invoice,
                            ]) }}"
                            class="mt-1 inline-block font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {{ $productReturn->invoice->invoice_number }}
                        </a>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Customer') }}
                        </p>

                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $productReturn->invoice->customer?->name ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Return Date') }}
                        </p>

                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $productReturn->return_date->format('Y-m-d') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Created By') }}
                        </p>

                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $productReturn->user->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Subtotal') }}
                        </p>

                        <p class="mt-1 font-semibold text-gray-900 dark:text-gray-100">
                            {{ number_format($productReturn->subtotal, 2) }}
                        </p>
                    </div>
                </div>

                @if ($productReturn->reason)
                    <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Reason') }}
                        </p>

                        <p class="mt-2 whitespace-pre-line text-gray-700 dark:text-gray-300">
                            {{ $productReturn->reason }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Returned Items') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Product') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('SKU') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Quantity') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Unit Price') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Line Total') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @foreach ($productReturn->items as $item)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $item->product->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $item->product->sku }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $item->quantity }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ number_format($item->unit_price, 2) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ number_format($item->line_total, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($productReturn->stockMovements->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('Stock Movements') }}
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ __('Product') }}
                                    </th>

                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ __('Quantity Change') }}
                                    </th>

                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ __('Before') }}
                                    </th>

                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ __('After') }}
                                    </th>

                                    <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ __('User') }}
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($productReturn->stockMovements as $movement)
                                    <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $movement->product->name ?? $productReturn->items->firstWhere('product_id', $movement->product_id)?->product?->name ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm font-medium text-green-700 dark:text-green-400">
                                            +{{ $movement->quantity_change }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $movement->quantity_before }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $movement->quantity_after }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $movement->user->name }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>