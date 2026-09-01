<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <form
                        method="POST"
                        action="{{ route('invoices.store', [
                            'type' => $invoiceType->value,
                        ]) }}"
                        class="space-y-6"
                        x-data="invoiceForm()"
                        data-products="{{ $products->toJson() }}"
                        data-invoice-type="{{ $invoiceType->value }}"
                        data-tax-rate="{{ (string) $taxRate }}"
                        data-previous-items="{{ json_encode(old('items', [])) }}"
                        data-discount-type="{{ old('discount_type', '') }}"
                        data-stock-label="{{ __('Stock') }}"
                        data-discount-value="{{ old('discount_value', '0') }}"
                    >
                        @csrf

                        <!-- Customer / Supplier Selection -->
                        @if($invoiceType === \App\Enums\InvoiceType::Sale)
                            <div>
                                <x-input-label for="customer_id" :value="__('Customer')" />
                                <select id="customer_id" name="customer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">{{ __('Select a customer') }}</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                            </div>
                        @else
                            <div>
                                <x-input-label for="supplier_id" :value="__('Supplier')" />
                                <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">{{ __('Select a supplier') }}</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                            </div>
                        @endif

                        <!-- Invoice Date -->
                        <div>
                            <x-input-label for="invoice_date" :value="__('Invoice Date')" />
                            <x-text-input id="invoice_date" name="invoice_date" type="date" class="mt-1 block w-full" :value="old('invoice_date', date('Y-m-d'))" required />
                            <x-input-error :messages="$errors->get('invoice_date')" class="mt-2" />
                        </div>

                        <!-- Items Section -->
                        <div>
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ __('Items') }}</h3>
                                <button
                                    id="add-item"
                                    type="button"
                                    @click="addItem()"
                                    :disabled="items.length >= 50"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{ __('Add Product') }}
                                </button>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Product') }}</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Quantity') }}</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Unit Price') }}</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Line Total') }}</th>
                                        <th class="px-3 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody
                                    id="invoice-items"
                                    class="divide-y divide-gray-200"
                                >
                                    <template
                                        x-for="(item, index) in items"
                                        :key="item.id"
                                    >
                                        <tr>
                                            <td class="px-3 py-3">
                                                <select
                                                    x-model="item.product_id"
                                                    @change="setDefaultUnitPrice(item)"
                                                    :name="'items[' + index + '][product_id]'"
                                                    class="product-select block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    required
                                                >
                                                    <option value="">
                                                        {{ __('Select a product') }}
                                                    </option>

                                                    <template
                                                        x-for="product in products"
                                                        :key="product.id"
                                                    >
                                                        <option
                                                            :value="String(product.id)"
                                                            x-text="productLabel(product)"
                                                        ></option>
                                                    </template>
                                                </select>
                                            </td>

                                            <td class="px-3 py-3">
                                                <input
                                                    x-model.number="item.quantity"
                                                    :name="'items[' + index + '][quantity]'"
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    required
                                                    class="quantity-input block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </td>

                                            <td class="unit-price px-3 py-3 text-sm text-gray-700">
                                                <input
                                                    type="number"
                                                    min="0.01"
                                                    step="0.01"
                                                    required
                                                    x-model="item.unit_price"
                                                    :name="'items[' + index + '][unit_price]'"
                                                    class="block w-full rounded-md border-gray-300 shadow-sm"
                                                >
                                            </td>

                                            <td
                                                class="line-total px-3 py-3 text-sm font-medium text-gray-900"
                                                x-text="formatCents(lineTotalInCents(item))"
                                            >
                                                0.00
                                            </td>

                                            <td class="px-3 py-3">
                                                <button
                                                    type="button"
                                                    @click="removeItem(item.id)"
                                                    :disabled="items.length <= 1"
                                                    class="text-sm font-medium text-red-600 hover:text-red-800 disabled:cursor-not-allowed disabled:opacity-50"
                                                >
                                                    {{ __('Remove') }}
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <x-input-error :messages="$errors->get('items')" class="mt-2" />
                        </div>

                        <!-- Discount & Tax & Totals Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="discount_type" :value="__('Discount Type')" />
                                <select
                                    id="discount_type"
                                    name="discount_type"
                                    x-model="discountType"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">{{ __('None') }}</option>
                                    <option value="fixed" @selected(old('discount_type') == 'fixed')>{{ __('Fixed') }}</option>
                                    <option value="percentage" @selected(old('discount_type') == 'percentage')>{{ __('Percentage') }}</option>
                                </select>
                                <x-input-error :messages="$errors->get('discount_type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="discount_value" :value="__('Discount Value')" />
                                <x-text-input
                                    id="discount_value"
                                    name="discount_value"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    x-model="discountValue"
                                    class="mt-1 block w-full"
                                    :value="old('discount_value', '0')"
                                />
                                <x-input-error :messages="$errors->get('discount_value')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea id="notes" name="notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <!-- Summary / Totals Preview -->
                        <div class="bg-gray-50 p-4 rounded-md space-y-2 text-right">
                            <div class="text-sm text-gray-600">
                                {{ __('Subtotal') }}: <span id="subtotal-preview" x-text="formatCents(subtotalInCents)">0.00</span>
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ __('Discount') }}: <span id="discount-preview" x-text="formatCents(discountInCents)">0.00</span>
                            </div>
                            <div class="text-sm text-gray-600">
                                {{ __('Tax') }} ({{ $taxRate }}%): <span id="tax-preview" x-text="formatCents(taxInCents)">0.00</span>
                            </div>
                            <div class="text-lg font-bold text-gray-900">
                                {{ __('Total') }}: <span id="total-preview" x-text="formatCents(totalInCents)">0.00</span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <x-primary-button>
                                {{ __('Save Invoice') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>