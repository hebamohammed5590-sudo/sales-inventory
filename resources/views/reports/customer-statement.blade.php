<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                {{ __('Customer Statement') }}:
                {{ $report['customer']->name }}
            </h2>

            <a
                href="{{ route('reports.index') }}"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                {{ __('Back to Reports') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <!-- Customer Information -->
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <div class="grid gap-6 md:grid-cols-3">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('Customer') }}
                        </p>

                        <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $report['customer']->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('Phone') }}
                        </p>

                        <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $report['customer']->phone }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('Email') }}
                        </p>

                        <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ $report['customer']->email ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
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
                            class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>
            </div>

            <!-- Summary -->
            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Total Invoiced') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ money($report['total_invoiced']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Total Paid') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-green-700 dark:text-green-400">
                        {{ money($report['total_paid']) }}
                        {{ $currency }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ __('Outstanding Balance') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-orange-700 dark:text-orange-400">
                        {{ money($report['balance']) }}
                        {{ $currency }}
                    </p>
                </div>
            </div>

            <!-- Invoices -->
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Invoices') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Invoice Number') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Date') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Total') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Paid') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Remaining') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Status') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Action') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($report['invoices'] as $invoice)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $invoice->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $invoice->invoice_date->format('Y-m-d') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ money($invoice->getRawOriginal('total')) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-green-700 dark:text-green-400">
                                        {{ money($invoice->paidAmountInCents()) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-orange-700 dark:text-orange-400">
                                        {{ money($invoice->remainingAmountInCents()) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ __(
                                            (string) str($invoice->status->value)
                                                ->replace('_', ' ')
                                                ->title()
                                        ) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <a
                                            href="{{ route('invoices.show', [
                                                'type' => $invoice->type->value,
                                                'invoice' => $invoice,
                                            ]) }}"
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
                                        {{ __('No invoices found for this customer.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <div class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-900">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-gray-100">
                    {{ __('Payment History') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Date') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Invoice') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Amount') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Method') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Reference') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ __('Recorded By') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            @forelse ($report['payments'] as $payment)
                                <tr class="transition hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $payment->paid_at->format('Y-m-d H:i') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $payment->payable->invoice_number }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-green-700 dark:text-green-400">
                                        {{ money($payment->getRawOriginal('amount')) }}
                                        {{ $currency }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ __(
                                            (string) str($payment->method->value)
                                                ->replace('_', ' ')
                                                ->title()
                                        ) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $payment->reference ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $payment->user->name }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
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