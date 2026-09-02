<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                {{ __('Product Returns') }}
            </h2>

            <a
                href="{{ route('invoices.index', ['type' => 'sale']) }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ __('Back to Sales Invoices') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg bg-green-100 p-4 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-900">
                <form
                    method="GET"
                    action="{{ route('product-returns.index') }}"
                    class="grid gap-4 p-6 md:grid-cols-2"
                >
                    <div>
                        <x-input-label
                            for="search"
                            :value="__('Search')"
                        />

                        <x-text-input
                            id="search"
                            name="search"
                            type="text"
                            class="mt-1 block w-full"
                            :value="request('search')"
                            placeholder="{{ __('Search return, invoice, or customer') }}"
                        />
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            {{ __('Search') }}
                        </x-primary-button>

                        <a
                            href="{{ route('product-returns.index') }}"
                            class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-900">
                <div class="overflow-x-auto p-6">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Return Number') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Invoice Number') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Customer') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Return Date') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Subtotal') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Created By') }}
                                </th>

                                <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($productReturns as $productReturn)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $productReturn->return_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <a
                                            href="{{ route('invoices.show', [
                                                'type' => 'sale',
                                                'invoice' => $productReturn->invoice,
                                            ]) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            {{ $productReturn->invoice->invoice_number }}
                                        </a>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $productReturn->invoice->customer?->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $productReturn->return_date->format('Y-m-d') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ number_format($productReturn->subtotal, 2) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $productReturn->user->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            href="{{ route('product-returns.show', $productReturn) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                                        >
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        {{ __('No product returns found.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-6">
                        {{ $productReturns->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>