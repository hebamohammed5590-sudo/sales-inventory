<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Today's Sales
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ number_format($dashboard['today_sales'] / 100, 2) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        This Month's Sales
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ number_format($dashboard['monthly_sales'] / 100, 2) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Inventory Value
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ number_format($dashboard['inventory_value'] / 100, 2) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Invoices Today
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ $dashboard['today_invoices'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Total Customers
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ $dashboard['total_customers'] }}
                    </p>
                </div>
            </div>

            <!-- Charts Section Added Here -->
            <div
                id="dashboard-charts"
                data-monthly-sales='@json($charts["monthly_sales"])'
                data-top-products='@json($charts["top_products"])'
                data-sales-by-category='@json($charts["sales_by_category"])'
                data-sales-vs-purchases='@json($charts["sales_vs_purchases"])'
                data-currency="{{ $currency }}"
                class="grid gap-6 lg:grid-cols-2"
            >
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Monthly Sales
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Sales during the last 12 months.
                        </p>
                    </div>

                    <div class="relative h-80">
                        <canvas id="monthly-sales-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Top Selling Products
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            The five products with the highest sales quantity.
                        </p>
                    </div>

                    <div class="relative h-80">
                        <canvas id="top-products-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Sales by Category
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Sales distribution across product categories.
                        </p>
                    </div>

                    <div class="relative h-80">
                        <canvas id="sales-by-category-chart"></canvas>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800">
                            Sales vs Purchases
                        </h3>

                        <p class="mt-1 text-sm text-gray-500">
                            Monthly comparison for the last six months.
                        </p>
                    </div>

                    <div class="relative h-80">
                        <canvas id="sales-vs-purchases-chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Low Stock Products
                    </h3>

                    @can('viewAny', \App\Models\Product::class)
                        <a
                            href="{{ route('products.index') }}"
                            class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                        >
                            View Products
                        </a>
                    @endcan
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Product
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    SKU
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Current Stock
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Reorder Level
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($dashboard['low_stock_products'] as $product)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $product->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->sku }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <span class="rounded-full bg-red-100 px-3 py-1 font-medium text-red-800">
                                            {{ $product->quantity }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $product->reorder_level }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="4"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No low stock products.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    Latest Invoices
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Invoice Number
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Customer / Supplier
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Total
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($dashboard['latest_invoices'] as $invoice)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $invoice->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ str($invoice->type->value)->title() }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if ($invoice->isSale())
                                            {{ $invoice->customer?->name ?? '-' }}
                                        @else
                                            {{ $invoice->supplier?->name ?? '-' }}
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ number_format($invoice->total / 100, 2) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ str($invoice->status->value)->replace('_', ' ')->title() }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            href="{{ route('invoices.show', [
                                                'type' => $invoice->type->value,
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
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No invoices found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    Unpaid and Partially Paid Invoices
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Invoice Number
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Total
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Paid
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Remaining
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($dashboard['unpaid_invoices'] as $invoice)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $invoice->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ str($invoice->type->value)->title() }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ number_format($invoice->total / 100, 2) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-green-700">
                                        {{ number_format($invoice->paidAmount() / 100, 2) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-orange-700">
                                        {{ number_format($invoice->remainingAmount() / 100, 2) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            href="{{ route('invoices.show', [
                                                'type' => $invoice->type->value,
                                                'invoice' => $invoice,
                                            ]) }}"
                                            class="font-weight text-indigo-600 hover:text-indigo-800"
                                        >
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No unpaid invoices.
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