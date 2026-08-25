<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Customer Statement:
                {{ $report['customer']->name }}
            </h2>

            <a
                href="{{ route('reports.index') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
            >
                Back to Reports
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Customer
                        </p>

                        <p class="mt-2 text-lg font-semibold text-gray-900">
                            {{ $report['customer']->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Phone
                        </p>

                        <p class="mt-2 text-lg font-semibold text-gray-900">
                            {{ $report['customer']->phone }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            Email
                        </p>

                        <p class="mt-2 text-lg font-semibold text-gray-900">
                            {{ $report['customer']->email ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <form
                    method="GET"
                    action="{{ route('reports.customers.statement', [
                        'customer' => $report['customer'],
                    ]) }}"
                    class="grid gap-4 md:grid-cols-3"
                >
                    <div>
                        <x-input-label
                            for="from"
                            value="From"
                        />

                        <x-text-input
                            id="from"
                            name="from"
                            type="date"
                            class="mt-1 block w-full"
                            :value="$report['from']->format('Y-m-d')"
                        />
                    </div>

                    <div>
                        <x-input-label
                            for="to"
                            value="To"
                        />

                        <x-text-input
                            id="to"
                            name="to"
                            type="date"
                            class="mt-1 block w-full"
                            :value="$report['to']->format('Y-m-d')"
                        />
                    </div>

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            Apply Filter
                        </x-primary-button>

                        <a
                            href="{{ route('reports.customers.statement', [
                                'customer' => $report['customer'],
                            ]) }}"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Total Invoiced
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ number_format($report['total_invoiced'] / 100, 2) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Total Paid
                    </p>

                    <p class="mt-3 text-2xl font-bold text-green-700">
                        {{ number_format($report['total_paid'] / 100, 2) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Outstanding Balance
                    </p>

                    <p class="mt-3 text-2xl font-bold text-orange-700">
                        {{ number_format($report['balance'] / 100, 2) }}
                        {{ $currency }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    Invoices
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Invoice Number
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Date
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
                                    Status
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($report['invoices'] as $invoice)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $invoice->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $invoice->invoice_date->format('Y-m-d') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $invoice->total }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-green-700">
                                        {{ $invoice->paidAmount() }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-orange-700">
                                        {{ $invoice->remainingAmount() }}
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
                                        colspan="7"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No invoices found for this customer.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    Payment History
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Date
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    Invoice
                                </th>

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
                                    Recorded By
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($report['payments'] as $payment)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $payment->paid_at->format('Y-m-d H:i') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $payment->payable->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-green-700">
                                        {{ $payment->amount }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ str($payment->method->value)->replace('_', ' ')->title() }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $payment->reference ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $payment->user->name }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        No payments found for this period.
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