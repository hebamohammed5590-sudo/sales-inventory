<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Stock Adjustments') }}
            </h2>

            @can('create', \App\Models\StockAdjustment::class)
                <a
                    href="{{ route('stock-adjustments.create') }}"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Add Adjustment
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-lg bg-green-100 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto p-6">
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
                                    Quantity Change
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Reason
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    User
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Date
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($adjustments as $adjustment)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $adjustment->product->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $adjustment->product->sku }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @if ($adjustment->quantity_change > 0)
                                            <span class="rounded-full bg-green-100 px-3 py-1 text-green-800">
                                                +{{ $adjustment->quantity_change }}
                                            </span>
                                        @else
                                            <span class="rounded-full bg-red-100 px-3 py-1 text-red-800">
                                                {{ $adjustment->quantity_change }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $adjustment->notes }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $adjustment->user->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $adjustment->created_at->format('Y-m-d H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                  <td
    colspan="6"
    class="px-4 py-8 text-center text-sm text-gray-500"
>
    {{ __('No stock adjustments found.') }}
</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $adjustments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>