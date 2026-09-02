<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                {{ __('Add Stock Adjustment') }}
            </h2>

            <a
                href="{{ route('stock-adjustments.index') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ __('Back to Adjustments') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-900">
                <div class="p-6">
                    <form
                        method="POST"
                        action="{{ route('stock-adjustments.store') }}"
                        class="space-y-6"
                    >
                        @csrf

                        <div>
                            <x-input-label
                                for="product_id"
                                :value="__('Product')"
                            />

                            <select
                                id="product_id"
                                name="product_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                required
                            >
                                <option value="">
                                    {{ __('Select a product') }}
                                </option>

                                @foreach ($products as $product)
                                    <option
                                        value="{{ $product->id }}"
                                        @selected(old('product_id') == $product->id)
                                    >
                                        {{ $product->name }}
                                        —
                                        {{ $product->sku }}
                                        —
                                        {{ __('Current stock') }}: {{ $product->quantity }}
                                    </option>
                                @endforeach
                            </select>

                            <x-input-error
                                :messages="$errors->get('product_id')"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="quantity_change"
                                :value="__('Quantity Change')"
                            />

                            <x-text-input
                                id="quantity_change"
                                name="quantity_change"
                                type="number"
                                step="1"
                                class="mt-1 block w-full"
                                :value="old('quantity_change')"
                                required
                            />

                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('Use a positive number to add stock or a negative number to remove stock.') }}
                            </p>

                            <x-input-error
                                :messages="$errors->get('quantity_change')"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="notes"
                                :value="__('Reason')"
                            />

                            <textarea
                                id="notes"
                                name="notes"
                                rows="4"
                                maxlength="1000"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                                required
                            >{{ old('notes') }}</textarea>

                            <x-input-error
                                :messages="$errors->get('notes')"
                                class="mt-2"
                            />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>
                                {{ __('Save Adjustment') }}
                            </x-primary-button>

                            <a
                                href="{{ route('stock-adjustments.index') }}"
                                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            >
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>