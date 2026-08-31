<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Customer Statement') }}:
                {{ $report['customer']->name }}
            </h2>

            <a
                href="{{ route('reports.index') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800"
            >
                {{ __('Back to Reports') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm">
                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            {{ __('Customer') }}
                        </p>

                        <p class="mt-2 text-lg font-semibold text-gray-900">
                            {{ $report['customer']->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            {{ __('Phone') }}
                        </p>

                        <p class="mt-2 text-lg font-semibold text-gray-900">
                            {{ $report['customer']->phone }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500">
                            {{ __('Email') }}
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
                            :value="__('From')"
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
                            :value="__('To')"
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
                            {{ __('Apply Filter') }}
                        </x-primary-button>

                        <a
                            href="{{ route('reports.customers.statement', [
                                'customer' => $report['customer'],
                            ]) }}"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Total Invoiced') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ money($report['total_invoiced']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Total Paid') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-green-700">
                        {{ money($report['total_paid']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Outstanding Balance') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-orange-700">
                        {{ money($report['balance']) }}
                        {{ $currency }}
                    </p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    {{ __('Invoices') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Invoice Number') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Date') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Total') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Paid') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Remaining') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Status') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Action') }}
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
                                        {{ money($invoice->getRawOriginal('total')) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-green-700">
                                        {{ money($invoice->paidAmountInCents()) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-orange-700">
                                        {{ money($invoice->remainingAmountInCents()) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ __((string) str($invoice->status->value)->replace('_', ' ')->title()) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            href="{{ route('invoices.show', [
                                                'type' => $invoice->type->value,
                                                'invoice' => $invoice,
                                            ]) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-800"
                                        >
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        {{ __('No invoices found for this customer.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    {{ __('Payment History') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Date') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Invoice') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Amount') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Method') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Reference') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Recorded By') }}
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
                                        {{ money($payment->getRawOriginal('amount')) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ __((string) str($payment->method->value)->replace('_', ' ')->title()) }}
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
                                        {{ __('No payments found for this period.') }}
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