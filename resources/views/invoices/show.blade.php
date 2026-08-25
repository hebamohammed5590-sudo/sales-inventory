<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    @if ($invoiceType === \App\Enums\InvoiceType::Sale)
                        Sales Invoice
                    @else
                        Purchase Invoice
                    @endif

                    {{ $invoice->invoice_number }}
                </h2>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a
    href="{{ route('invoices.print', [
        'type' => $invoiceType->value,
        'invoice' => $invoice,
    ]) }}"
    target="_blank"
    rel="noopener noreferrer"
    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
>
    Print Invoice
</a>
                @can('confirm', $invoice)
                    <form
                        method="POST"
                        action="{{ route('invoices.confirm', [
                            'type' => $invoiceType->value,
                            'invoice' => $invoice,
                        ]) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700"
                        >
                            Confirm Invoice
                        </button>
                    </form>
                @endcan

                @can('cancel', $invoice)
                    <form
                        method="POST"
                        action="{{ route('invoices.cancel', [
                            'type' => $invoiceType->value,
                            'invoice' => $invoice,
                        ]) }}"
                        onsubmit="return confirm('Are you sure you want to cancel this invoice?')"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                        >
                            Cancel Invoice
                        </button>
                    </form>
                @endcan

                <a
                    href="{{ route('invoices.index', [
                        'type' => $invoiceType->value,
                    ]) }}"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                >
                    Back to Invoices
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-6xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg bg-green-100 p-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-100 p-4 text-red-800">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    Invoice Information
                </h3>

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-sm text-gray-500">
                            Invoice Number
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ $invoice->invoice_number }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">
                            @if ($invoiceType === \App\Enums\InvoiceType::Sale)
                                Customer
                            @else
                                Supplier
                            @endif
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            @if ($invoiceType === \App\Enums\InvoiceType::Sale)
                                {{ $invoice->customer?->name ?? '-' }}
                            @else
                                {{ $invoice->supplier?->name ?? '-' }}
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">
                            Invoice Date
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ $invoice->invoice_date->format('Y-m-d') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">
                            Status
                        </p>

                        @php
                            $statusClasses = match ($invoice->status) {
                                \App\Enums\InvoiceStatus::Draft
                                    => 'bg-gray-100 text-gray-800',

                                \App\Enums\InvoiceStatus::Confirmed
                                    => 'bg-blue-100 text-blue-800',

                                \App\Enums\InvoiceStatus::PartiallyPaid
                                    => 'bg-yellow-100 text-yellow-800',

                                \App\Enums\InvoiceStatus::Paid
                                    => 'bg-green-100 text-green-800',

                                \App\Enums\InvoiceStatus::Cancelled
                                    => 'bg-red-100 text-red-800',
                            };
                        @endphp

                        <span class="mt-2 inline-flex rounded-full px-3 py-1 text-sm {{ $statusClasses }}">
                            {{ str($invoice->status->value)->replace('_', ' ')->title() }}
                        </span>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">
                            Created By
                        </p>

                        <p class="mt-1 font-medium text-gray-900">
                            {{ $invoice->user->name }}
                        </p>
                    </div>

                    @if ($invoice->confirmed_at)
                        <div>
                            <p class="text-sm text-gray-500">
                                Confirmed At
                            </p>

                            <p class="mt-1 font-medium text-gray-900">
                                {{ $invoice->confirmed_at->format('Y-m-d H:i') }}
                            </p>
                        </div>
                    @endif

                    @if ($invoice->cancelled_at)
                        <div>
                            <p class="text-sm text-gray-500">
                                Cancelled At
                            </p>

                            <p class="mt-1 font-medium text-gray-900">
                                {{ $invoice->cancelled_at->format('Y-m-d H:i') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    Invoice Items
                </h3>

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
                                    Quantity
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Unit Price
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Line Total
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $item->product->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $item->product->sku }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $item->quantity }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $item->unit_price }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $item->line_total }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="ml-auto max-w-sm space-y-3">
                    <div class="flex justify-between text-gray-700">
                        <span>
                            Subtotal
                        </span>

                        <span>
                            {{ $invoice->subtotal }}
                        </span>
                    </div>

                    <div class="flex justify-between text-gray-700">
                        <span>
                            Discount
                        </span>

                        <span>
                            {{ $invoice->discount }}
                        </span>
                    </div>

                    <div class="flex justify-between text-gray-700">
                        <span>
                            Tax
                        </span>

                        <span>
                            {{ $invoice->tax }}
                        </span>
                    </div>

                    <div class="flex justify-between border-t pt-3 text-lg font-semibold text-gray-900">
                        <span>
                            Total
                        </span>

                        <span>
                            {{ $invoice->total }}
                        </span>
                    </div>

                    <div class="flex justify-between text-green-700">
                        <span>
                            Paid
                        </span>

                        <span>
                            {{ $invoice->paidAmount() }}
                        </span>
                    </div>

                    <div class="flex justify-between font-semibold text-orange-700">
                        <span>
                            Remaining
                        </span>

                        <span>
                            {{ $invoice->remainingAmount() }}
                        </span>
                    </div>
                </div>
            </div>

            @if (
                in_array(
                    $invoice->status,
                    [
                        \App\Enums\InvoiceStatus::Confirmed,
                        \App\Enums\InvoiceStatus::PartiallyPaid,
                    ],
                    true
                )
            )
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800">
                        Record Payment
                    </h3>

                    <form
                        method="POST"
                        action="{{ route('invoices.payments.store', [
                            'type' => $invoiceType->value,
                            'invoice' => $invoice,
                        ]) }}"
                        class="grid gap-6 md:grid-cols-2"
                    >
                        @csrf

                        <div>
                            <x-input-label
                                for="amount"
                                value="Amount"
                            />

                            <x-text-input
                                id="amount"
                                name="amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                class="mt-1 block w-full"
                                :value="old('amount')"
                                required
                            />

                            <p class="mt-2 text-sm text-gray-500">
                                Remaining balance:
                                {{ $invoice->remainingAmount() }}
                            </p>

                            <x-input-error
                                :messages="$errors->get('amount')"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="method"
                                value="Payment Method"
                            />

                            <select
                                id="method"
                                name="method"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                                <option value="">
                                    Select payment method
                                </option>

                                @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                    <option
                                        value="{{ $method->value }}"
                                        @selected(
                                            old('method') === $method->value
                                        )
                                    >
                                        {{ str($method->value)->replace('_', ' ')->title() }}
                                    </option>
                                @endforeach
                            </select>

                            <x-input-error
                                :messages="$errors->get('method')"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="reference"
                                value="Reference"
                            />

                            <x-text-input
                                id="reference"
                                name="reference"
                                type="text"
                                class="mt-1 block w-full"
                                :value="old('reference')"
                                placeholder="Optional payment reference"
                            />

                            <x-input-error
                                :messages="$errors->get('reference')"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <x-input-label
                                for="paid_at"
                                value="Payment Date"
                            />

                            <x-text-input
                                id="paid_at"
                                name="paid_at"
                                type="datetime-local"
                                class="mt-1 block w-full"
                                :value="old(
                                    'paid_at',
                                    now()->format('Y-m-d\TH:i')
                                )"
                            />

                            <x-input-error
                                :messages="$errors->get('paid_at')"
                                class="mt-2"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <x-input-label
                                for="payment_notes"
                                value="Notes"
                            />

                            <textarea
                                id="payment_notes"
                                name="notes"
                                rows="3"
                                maxlength="1000"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >{{ old('notes') }}</textarea>

                            <x-input-error
                                :messages="$errors->get('notes')"
                                class="mt-2"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <x-primary-button>
                                Record Payment
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($invoice->payments->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800">
                        Payment History
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        Amount
                                    </th>

                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        Method
                                    </th>

                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        Reference
                                    </th>

                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        Paid At
                                    </th>

                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        Recorded By
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                @foreach ($invoice->payments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-green-700">
                                            {{ $payment->amount }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ str($payment->method->value)->replace('_', ' ')->title() }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $payment->reference ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $payment->paid_at->format('Y-m-d H:i') }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $payment->user->name }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if ($invoice->notes)
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-3 text-lg font-semibold text-gray-800">
                        Notes
                    </h3>

                    <p class="whitespace-pre-line text-gray-700">
                        {{ $invoice->notes }}
                    </p>
                </div>
            @endif

            @if ($invoice->stockMovements->isNotEmpty())
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="mb-6 text-lg font-semibold text-gray-800">
                        Stock Movements
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        Type
                                    </th>

                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        Quantity Change
                                    </th>

                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        Before
                                    </th>

                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        After
                                    </th>

                                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                        User
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                @foreach ($invoice->stockMovements as $movement)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ str($movement->type->value)->title() }}
                                        </td>

                                        <td class="px-4 py-3 text-sm font-medium">
                                            @if ($movement->quantity_change > 0)
                                                <span class="text-green-700">
                                                    +{{ $movement->quantity_change }}
                                                </span>
                                            @else
                                                <span class="text-red-700">
                                                    {{ $movement->quantity_change }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $movement->quantity_before }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600">
                                            {{ $movement->quantity_after }}
                                        </td>

                                        <td class="px-4 py-3 text-sm text-gray-600">
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