<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                    {{ __('Create Product Return') }}
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Invoice Number') }}:
                    {{ $invoice->invoice_number }}
                </p>
            </div>

            <a
                href="{{ route('invoices.show', [
                    'type' => 'sale',
                    'invoice' => $invoice,
                ]) }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ __('Back to Invoice') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Invoice Information') }}
                </h3>

                <div class="grid gap-6 md:grid-cols-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Invoice Number') }}
                        </p>

                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $invoice->invoice_number }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Customer') }}
                        </p>

                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $invoice->customer?->name ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Invoice Date') }}
                        </p>

                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ $invoice->invoice_date->format('Y-m-d') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Status') }}
                        </p>

                        <p class="mt-1 font-medium text-gray-900 dark:text-gray-100">
                            {{ __((string) str($invoice->status->value)->replace('_', ' ')->title()) }}
                        </p>
                    </div>
                </div>
            </div>

            @if (! $hasReturnableItems)
                <div class="rounded-lg bg-yellow-100 p-4 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300">
                    {{ __('No quantities are available to return.') }}
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('product-returns.store', $invoice) }}"
                class="space-y-6"
                x-data="{ submitting: false }"
                @submit="submitting = true"
            >
                @csrf

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <x-input-label
                                for="return_date"
                                :value="__('Return Date')"
                            />

                            <x-text-input
                                id="return_date"
                                name="return_date"
                                type="date"
                                class="mt-1 block w-full"
                                :value="old('return_date', date('Y-m-d'))"
                                :max="date('Y-m-d')"
                            />

                            <x-input-error
                                :messages="$errors->get('return_date')"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="reason"
                                :value="__('Reason')"
                            />

                            <textarea
                                id="reason"
                                name="reason"
                                rows="3"
                                maxlength="2000"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                            >{{ old('reason') }}</textarea>

                            <x-input-error
                                :messages="$errors->get('reason')"
                                class="mt-2"
                            />
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-900">
                    <div class="p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                                {{ __('Returned Items') }}
                            </h3>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Return subtotal is calculated from the original invoice item prices.') }}
                            </p>
                        </div>

                        <x-input-error
                            :messages="$errors->get('items')"
                            class="mb-4"
                        />

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
                                            {{ __('Sold Quantity') }}
                                        </th>

                                        <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ __('Already Returned') }}
                                        </th>

                                        <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ __('Returnable Quantity') }}
                                        </th>

                                        <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ __('Unit Price') }}
                                        </th>

                                        <th class="px-4 py-3 text-start text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            {{ __('Return Quantity') }}
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                                    @foreach ($invoice->items as $item)
                                        @php
                                            $returnedQuantity = (int) ($item->returned_quantity ?? 0);

                                            $returnableQuantity = max(
                                                0,
                                                $item->quantity - $returnedQuantity
                                            );
                                        @endphp

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
                                                {{ $returnedQuantity }}
                                            </td>

                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $returnableQuantity }}
                                            </td>

                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                                {{ number_format($item->unit_price, 2) }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <input
                                                    type="number"
                                                    name="items[{{ $item->id }}]"
                                                    min="0"
                                                    max="{{ $returnableQuantity }}"
                                                    step="1"
                                                    value="{{ old('items.'.$item->id, 0) }}"
                                                    @disabled($returnableQuantity === 0)
                                                    class="block w-24 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:disabled:bg-gray-700"
                                                >

                                                <x-input-error
                                                    :messages="$errors->get('items.'.$item->id)"
                                                    class="mt-2"
                                                />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <x-primary-button
                        ::disabled="submitting || {{ $hasReturnableItems ? 'false' : 'true' }}"
                        ::class="{ 'opacity-50 cursor-not-allowed': submitting }"
                    >
                        {{ __('Create Return') }}
                    </x-primary-button>

                    <a
                        href="{{ route('invoices.show', [
                            'type' => 'sale',
                            'invoice' => $invoice,
                        ]) }}"
                        class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                    >
                        {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>