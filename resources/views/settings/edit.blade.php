<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Settings
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg bg-green-100 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-100 p-4 text-red-800">
                    <p class="font-medium">
                        Please correct the following errors:
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

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800">
                        Company Information
                    </h3>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="company_name"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Company Name
                            </label>

                            <input
                                id="company_name"
                                name="company_name"
                                type="text"
                                value="{{ old('company_name', $settings['company_name']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div>
                            <label
                                for="company_phone"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Company Phone
                            </label>

                            <input
                                id="company_phone"
                                name="company_phone"
                                type="text"
                                value="{{ old('company_phone', $settings['company_phone']) }}"
                                class="block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="company_address"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Company Address
                            </label>

                            <textarea
                                id="company_address"
                                name="company_address"
                                rows="3"
                                class="block w-full rounded-md border-gray-300 shadow-sm"
                            >{{ old('company_address', $settings['company_address']) }}</textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label
                                for="company_logo"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Company Logo
                            </label>

                            @if (filled($settings['company_logo']))
                                <img
                                    src="{{ asset('storage/' . $settings['company_logo']) }}"
                                    alt="Company Logo"
                                    class="mb-3 h-20 w-auto rounded-md border object-contain"
                                >
                            @endif

                            <input
                                id="company_logo"
                                name="company_logo"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="block w-full rounded-md border border-gray-300 px-3 py-2"
                            >
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800">
                        Financial Settings
                    </h3>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label
                                for="currency_symbol"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Currency Symbol
                            </label>

                            <input
                                id="currency_symbol"
                                name="currency_symbol"
                                type="text"
                                value="{{ old('currency_symbol', $settings['currency_symbol']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div>
                            <label
                                for="tax_rate"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Tax Rate %
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
                                class="block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div>
                            <label
                                for="invoice_prefix"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Sales Invoice Prefix
                            </label>

                            <input
                                id="invoice_prefix"
                                name="invoice_prefix"
                                type="text"
                                value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div>
                            <label
                                for="purchase_invoice_prefix"
                                class="mb-2 block text-sm font-medium text-gray-700"
                            >
                                Purchase Invoice Prefix
                            </label>

                            <input
                                id="purchase_invoice_prefix"
                                name="purchase_invoice_prefix"
                                type="text"
                                value="{{ old('purchase_invoice_prefix', $settings['purchase_invoice_prefix']) }}"
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800">
                        Inventory Settings
                    </h3>

                    <div class="max-w-sm">
                        <label
                            for="low_stock_threshold"
                            class="mb-2 block text-sm font-medium text-gray-700"
                        >
                            Low Stock Threshold
                        </label>

                        <input
                            id="low_stock_threshold"
                            name="low_stock_threshold"
                            type="number"
                            min="0"
                            step="1"
                            value="{{ old('low_stock_threshold', $settings['low_stock_threshold']) }}"
                            required
                            class="block w-full rounded-md border-gray-300 shadow-sm"
                        >
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>