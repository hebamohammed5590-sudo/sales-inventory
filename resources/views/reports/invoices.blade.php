<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                @if ($report['type'] === \App\Enums\InvoiceType::Sale)
                    {{ __('Sales Report') }}
                @else
                    {{ __('Purchases Report') }}
                @endif
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
                <form
                    method="GET"
                    action="{{ $report['type'] === \App\Enums\InvoiceType::Sale
                        ? route('reports.sales')
                        : route('reports.purchases') }}"
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
                            href="{{ $report['type'] === \App\Enums\InvoiceType::Sale
                                ? route('reports.sales')
                                : route('reports.purchases') }}"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            {{ __('Reset') }}
                        </a>
                    </div>
                </form>

                @if ($errors->any())
                    <div class="mt-4 rounded-md bg-red-100 p-4 text-sm text-red-800">
                        @foreach ($errors->all() as $error)
                            <p>
                                {{ $error }}
                            </p>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Number of Invoices') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ $report['count'] }}
                    </p>
                </div>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        {{ __('Total Amount') }}
                    </p>

                    <p class="mt-3 text-2xl font-bold text-gray-900">
                        {{ money($report['total']) }}
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
                                    @if ($report['type'] === \App\Enums\InvoiceType::Sale)
                                        {{ __('Customer') }}
                                    @else
                                        {{ __('Supplier') }}
                                    @endif
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Date') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Status') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Total') }}
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
                                        @if ($invoice->isSale())
                                            {{ $invoice->customer?->name ?? '-' }}
                                        @else
                                            {{ $invoice->supplier?->name ?? '-' }}
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $invoice->invoice_date->format('Y-m-d') }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ __((string) str($invoice->status->value)->replace('_', ' ')->title()) }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ money($invoice->getRawOriginal('total')) }}
                                        {{ $currency }}
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
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        {{ __('No invoices found for this period.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm">
                <h3 class="mb-6 text-lg font-semibold text-gray-800">
                    {{ __('Daily Totals') }}
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Date') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Number of Invoices') }}
                                </th>

                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">
                                    {{ __('Total') }}
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($report['daily_totals'] as $date => $dailyTotal)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $date }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $dailyTotal['count'] }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ money($dailyTotal['total']) }}
                                        {{ $currency }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="3"
                                        class="px-4 py-8 text-center text-sm text-gray-500"
                                    >
                                        {{ __('No daily totals available.') }}
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