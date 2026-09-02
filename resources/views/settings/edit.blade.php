<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
            {{ __('Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg bg-green-100 p-4 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-100 p-4 text-red-800 dark:bg-red-900/40 dark:text-red-300">
                    <p class="font-medium">
                        {{ __('Please correct the following errors:') }}
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                action="{{ route('settings.update') }}"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('Company Information') }}
                    </h3>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="company_name"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ __('Company Name') }}
                            </label>

                            <input
                                id="company_name"
                                name="company_name"
                                type="text"
                                value="{{ old('company_name', $settings['company_name']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label
                                for="company_phone"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ __('Company Phone') }}
                            </label>

                            <input
                                id="company_phone"
                                name="company_phone"
                                type="text"
                                value="{{ old('company_phone', $settings['company_phone']) }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="company_address"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ __('Company Address') }}
                            </label>

                            <textarea
                                id="company_address"
                                name="company_address"
                                rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >{{ old('company_address', $settings['company_address']) }}</textarea>
                        </div>

                        <div
                            class="md:col-span-2"
                            x-data="{ fileName: '' }"
                        >
                            <label
                                for="company_logo"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ __('Company Logo') }}
                            </label>

                            @if (filled($settings['company_logo']))
                                <img
                                    src="{{ asset('storage/' . $settings['company_logo']) }}"
                                    alt="{{ __('Company Logo') }}"
                                    class="mb-3 h-20 w-auto rounded-md border border-gray-200 object-contain dark:border-gray-700"
                                >
                            @endif

                            <input
                                id="company_logo"
                                name="company_logo"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="sr-only"
                                @change="fileName = $event.target.files.length ? $event.target.files[0].name : ''"
                            >

                            <div class="flex min-h-11 items-center gap-3 rounded-md border border-gray-300 bg-white px-3 py-2 dark:border-gray-600 dark:bg-gray-800">
                                <label
                                    for="company_logo"
                                    class="cursor-pointer rounded-md bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                                >
                                    {{ __('Choose File') }}
                                </label>

                                <span
                                    class="min-w-0 flex-1 truncate text-sm text-gray-500 dark:text-gray-400"
                                    x-text="fileName || @js(__('No file chosen'))"
                                    dir="auto"
                                ></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('Financial Settings') }}
                    </h3>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="currency_symbol"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ __('Currency Symbol') }}
                            </label>

                            <input
                                id="currency_symbol"
                                name="currency_symbol"
                                type="text"
                                value="{{ old('currency_symbol', $settings['currency_symbol']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label
                                for="tax_rate"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ __('Tax Rate %') }}
                            </label>

                            <input
                                id="tax_rate"
                                name="tax_rate"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value="{{ old('tax_rate', $settings['tax_rate']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label
                                for="invoice_prefix"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ __('Sales Invoice Prefix') }}
                            </label>

                            <input
                                id="invoice_prefix"
                                name="invoice_prefix"
                                type="text"
                                value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                        </div>

                        <div>
                            <label
                                for="purchase_invoice_prefix"
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                {{ __('Purchase Invoice Prefix') }}
                            </label>

                            <input
                                id="purchase_invoice_prefix"
                                name="purchase_invoice_prefix"
                                type="text"
                                value="{{ old('purchase_invoice_prefix', $settings['purchase_invoice_prefix']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                            >
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('Inventory Settings') }}
                    </h3>

                    <div class="max-w-sm">
                        <label
                            for="low_stock_threshold"
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ __('Low Stock Threshold') }}
                        </label>

                        <input
                            id="low_stock_threshold"
                            name="low_stock_threshold"
                            type="number"
                            min="0"
                            step="1"
                            value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}"
                            required
                            class="block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                    >
                        {{ __('Save Settings') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>