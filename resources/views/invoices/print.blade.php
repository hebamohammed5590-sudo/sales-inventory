<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        {{ $invoice->invoice_number }} - Invoice
    </title>

    <style>
        @page {
            size: A4;
            margin: 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            background: #f3f4f6;
        }

        .page {
            width: 100%;
            max-width: 900px;
            margin: 32px auto;
            padding: 40px;
            background: #ffffff;
        }

        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            max-width: 900px;
            margin: 24px auto 0;
        }

        .button {
            display: inline-block;
            padding: 10px 16px;
            color: #ffffff;
            background: #4f46e5;
            border: 0;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
        }

        .button-secondary {
            background: #374151;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
        }

        .company-name {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .invoice-title {
            margin: 0 0 8px;
            font-size: 24px;
            text-align: right;
        }

        .muted {
            color: #6b7280;
        }

        .details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .details-card {
            padding: 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }

        .details-card h3 {
            margin: 0 0 12px;
            font-size: 16px;
        }

        .detail-row {
            margin: 6px 0;
        }

        table {
            width: 100%;
            margin-top: 32px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            font-weight: 600;
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .totals {
            width: 320px;
            max-width: 100%;
            margin-top: 24px;
            margin-left: auto;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            padding: 9px 0;
        }

        .grand-total {
            padding-top: 12px;
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #111827;
        }

        .remaining {
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            padding-top: 18px;
            color: #6b7280;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .toolbar {
                display: none;
            }

            .page {
                max-width: none;
                margin: 0;
                padding: 0;
            }

            tr,
            .details-card {
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @php
        $isSale = $invoiceType === \App\Enums\InvoiceType::Sale;

        $party = $isSale
            ? $invoice->customer
            : $invoice->supplier;
    @endphp

    <div class="toolbar">
        <a
            href="{{ route('invoices.show', [
                'type' => $invoiceType->value,
                'invoice' => $invoice,
            ]) }}"
            class="button button-secondary"
        >
            {{ __('Back to Invoice') }}
        </a>

        <button
            type="button"
            class="button"
            onclick="window.print()"
        >
            {{ __('Print Invoice') }}
        </button>
    </div>

    <main class="page">
        <section class="header">
            <div>
                <h1 class="company-name">
                    {{ $companyName }}
                </h1>

                @if (filled($companyAddress))
                    <div class="muted">
                        {{ $companyAddress }}
                    </div>
                @endif

                @if (filled($companyPhone))
                    <div class="muted">
                        {{ $companyPhone }}
                    </div>
                @endif
            </div>

            <div>
                <h2 class="invoice-title">
                    {{ __($isSale ? 'Sales Invoice' : 'Purchase Invoice') }}
                </h2>

                <div class="text-right muted">
                    {{ $invoice->invoice_number }}
                </div>
            </div>
        </section>

        <section class="details">
            <div class="details-card">
                <h3>
                    {{ __($isSale ? 'Customer Information' : 'Supplier Information') }}
                </h3>

                <div class="detail-row">
                    <strong>{{ __('Name') }}:</strong>

                    {{ $party?->name ?? '-' }}
                </div>

                <div class="detail-row">
                    <strong>{{ __('Phone') }}:</strong>

                    {{ $party?->phone ?? '-' }}
                </div>

                <div class="detail-row">
                    <strong>{{ __('Email') }}:</strong>

                    {{ $party?->email ?? '-' }}
                </div>
            </div>

            <div class="details-card">
                <h3>
                    {{ __('Invoice Information') }}
                </h3>

                <div class="detail-row">
                    <strong>{{ __('Invoice Number') }}:</strong>

                    {{ $invoice->invoice_number }}
                </div>

                <div class="detail-row">
                    <strong>{{ __('Date') }}:</strong>

                    {{
                        $invoice->invoice_date instanceof \DateTimeInterface
                            ? $invoice->invoice_date->format('Y-m-d')
                            : $invoice->invoice_date
                    }}
                </div>

                <div class="detail-row">
                    <strong>{{ __('Status') }}:</strong>

                    {{ __(
                        str($invoice->status->value)
                            ->replace('_', ' ')
                            ->title()
                            ->toString()
                    ) }}
                </div>

                <div class="detail-row">
                    <strong>{{ __('Created By') }}:</strong>

                    {{ $invoice->user?->name ?? '-' }}
                </div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>
                        #
                    </th>

                    <th>
                        {{ __('Product') }}
                    </th>

                    <th>
                        {{ __('SKU') }}
                    </th>

                    <th class="text-right">
                        {{ __('Quantity') }}
                    </th>

                    <th class="text-right">
                        {{ __('Unit Price') }}
                    </th>

                    <th class="text-right">
                        {{ __('Total') }}
                    </th>
                </tr>
            </thead>

            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>
                            {{ $loop->iteration }}
                        </td>

                        <td>
                            {{ $item->product?->name ?? '-' }}
                        </td>

                        <td>
                            {{ $item->product?->sku ?? '-' }}
                        </td>

                        <td class="text-right">
                            {{ $item->quantity }}
                        </td>

                        <td class="text-right">
                            {{ $item->unit_price }}
                        </td>

                        <td class="text-right">
                            {{ $item->line_total }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="totals">
            <div class="total-row">
                <span>{{ __('Subtotal') }}</span>

                <span>
                    {{ $invoice->subtotal }} {{ $currencySymbol }}
                </span>
            </div>

            <div class="total-row">
                <span>{{ __('Discount') }}</span>

                <span>
                    {{ $invoice->discount }} {{ $currencySymbol }}
                </span>
            </div>

            <div class="total-row">
                <span>{{ __('Tax') }}</span>

                <span>
                    {{ $invoice->tax }} {{ $currencySymbol }}
                </span>
            </div>

            <div class="total-row grand-total">
                <span>{{ __('Total') }}</span>

                <span>
                    {{ $invoice->total }} {{ $currencySymbol }}
                </span>
            </div>

            <div class="total-row">
                <span>{{ __('Paid') }}</span>

                <span>
                    {{ $invoice->paidAmount() }} {{ $currencySymbol }}
                </span>
            </div>

            <div class="total-row remaining">
                <span>{{ __('Remaining') }}</span>

                <span>
                    {{ $invoice->remainingAmount() }} {{ $currencySymbol }}
                </span>
            </div>
        </section>

        <footer class="footer">
            {{ $companyName }}
        </footer>
    </main>
</body>
</html>