<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __("Today's Sales") }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ money($dashboard['today_sales']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __("This Month's Sales") }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ money($dashboard['monthly_sales']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Inventory Value') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ money($dashboard['inventory_value']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Invoices Today') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $dashboard['today_invoices'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Total Customers') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $dashboard['total_customers'] }}
                    </p>
                </div>
            </div>

            <!-- Charts Section -->
            <div
                id="dashboard-charts"
                data-monthly-sales='@json($charts["monthly_sales"])'
                data-top-products='@json($charts["top_products"])'
                data-sales-by-category='@json($charts["sales_by_category"])'
                data-sales-vs-purchases='@json($charts["sales_vs_purchases"])'
                data-currency="{{ $currency }}"
                class="grid gap-6 lg:grid-cols-2"
            >
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            {{ __('Monthly Sales') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Sales during the last 12 months.') }}
                        </p>
                    </div>

                    <div class="relative h-80">
                        <canvas id="monthly-sales-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            {{ __('Top Selling Products') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('The five products with the highest sales quantity.') }}
                        </p>
                    </div>

                    <div class="relative h-80">
                        <canvas id="top-products-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            {{ __('Sales by Category') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Sales distribution across product categories.') }}
                        </p>
                    </div>

                    <div class="relative h-80">
                        <canvas id="sales-by-category-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            {{ __('Sales vs Purchases') }}
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Monthly comparison for the last six months.') }}
                        </p>
                    </div>

                    <div class="relative h-80">
                        <canvas id="sales-vs-purchases-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('Low Stock Products') }}
                    </h3>

                    @can('viewAny', \App\Models\Product::class)
                        <a
                            href="{{ route('products.index') }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {{ __('View Products') }}
                        </a>
                    @endcan
                </div>

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
                                    {{ __('Current Stock') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Reorder Level') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($dashboard['low_stock_products'] as $product)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $product->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $product->sku }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <span class="rounded-full bg-red-100 px-3 py-1 font-medium text-red-800 dark:bg-red-900/40 dark:text-red-300">
                                            {{ $product->quantity }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $product->reorder_level }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ __('No low stock products.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Latest Invoices -->
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Latest Invoices') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Invoice Number') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Type') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Customer / Supplier') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Total') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Status') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Action') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($dashboard['latest_invoices'] as $invoice)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $invoice->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ __(str($invoice->type->value)->title()->toString()) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        @if ($invoice->isSale())
                                            {{ $invoice->customer?->name ?? '-' }}
                                        @else
                                            {{ $invoice->supplier?->name ?? '-' }}
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ money($invoice->getRawOriginal('total')) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ __(
                                            str($invoice->status->value)
                                                ->replace('_', ' ')
                                                ->title()
                                                ->toString()
                                        ) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            href="{{ route('invoices.show', [
                                                'type' => $invoice->type->value,
                                                'invoice' => $invoice,
                                            ]) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ __('No invoices found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Unpaid Invoices -->
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Unpaid and Partially Paid Invoices') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Invoice Number') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Type') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Total') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Paid') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Remaining') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Action') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($dashboard['unpaid_invoices'] as $invoice)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $invoice->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ __(str($invoice->type->value)->title()->toString()) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ money($invoice->getRawOriginal('total')) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-green-700 dark:text-green-400">
                                        {{ money($invoice->paidAmountInCents()) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-orange-700 dark:text-orange-400">
                                        {{ money($invoice->remainingAmountInCents()) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            href="{{ route('invoices.show', [
                                                'type' => $invoice->type->value,
                                                'invoice' => $invoice,
                                            ]) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ __('No unpaid invoices.') }}
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