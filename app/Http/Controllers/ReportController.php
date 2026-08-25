<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\CsvExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        Gate::authorize(
            'view-reports'
        );

        return view(
            'reports.index'
        );
    }

    public function sales(
        Request $request,
        ReportService $reportService
    ): View {
        Gate::authorize(
            'view-reports'
        );

        [
            $from,
            $to,
        ] = $this->resolveDateRange(
            $request
        );

        $report = $reportService->salesReport(
            $from,
            $to
        );

        $currency = $this->currency();

        return view(
            'reports.invoices',
            compact(
                'report',
                'currency'
            )
        );
    }

    public function purchases(
        Request $request,
        ReportService $reportService
    ): View {
        Gate::authorize(
            'view-reports'
        );

        [
            $from,
            $to,
        ] = $this->resolveDateRange(
            $request
        );

        $report = $reportService->purchasesReport(
            $from,
            $to
        );

        $currency = $this->currency();

        return view(
            'reports.invoices',
            compact(
                'report',
                'currency'
            )
        );
    }

    public function profit(
        Request $request,
        ReportService $reportService
    ): View {
        Gate::authorize(
            'view-reports'
        );

        [
            $from,
            $to,
        ] = $this->resolveDateRange(
            $request
        );

        $report = $reportService->profitReport(
            $from,
            $to
        );

        $currency = $this->currency();

        return view(
            'reports.profit',
            compact(
                'report',
                'currency'
            )
        );
    }

    public function stock(
        ReportService $reportService
    ): View {
        Gate::authorize(
            'view-reports'
        );

        $report = $reportService->stockReport();

        $currency = $this->currency();

        return view(
            'reports.stock',
            compact(
                'report',
                'currency'
            )
        );
    }

    public function customerStatement(
        Request $request,
        Customer $customer,
        ReportService $reportService
    ): View {
        Gate::authorize(
            'view-reports'
        );

        [
            $from,
            $to,
        ] = $this->resolveDateRange(
            $request
        );

        $report = $reportService->customerStatement(
            $customer,
            $from,
            $to
        );

        $currency = $this->currency();

        return view(
            'reports.customer-statement',
            compact(
                'report',
                'currency'
            )
        );
    }

    public function exportSales(
        Request $request,
        ReportService $reportService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        Gate::authorize(
            'view-reports'
        );

        [
            $from,
            $to,
        ] = $this->resolveDateRange(
            $request
        );

        $report = $reportService->salesReport(
            $from,
            $to
        );

        return $this->exportInvoiceReport(
            $report,
            $csvExportService
        );
    }

    public function exportPurchases(
        Request $request,
        ReportService $reportService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        Gate::authorize(
            'view-reports'
        );

        [
            $from,
            $to,
        ] = $this->resolveDateRange(
            $request
        );

        $report = $reportService->purchasesReport(
            $from,
            $to
        );

        return $this->exportInvoiceReport(
            $report,
            $csvExportService
        );
    }

    private function exportInvoiceReport(
        array $report,
        CsvExportService $csvExportService
    ): StreamedResponse {
        $isSale = $report['type'] === InvoiceType::Sale;

        $headers = [
            'Invoice Number',

            $isSale
                ? 'Customer'
                : 'Supplier',

            'Invoice Date',

            'Status',

            'Subtotal',

            'Discount',

            'Tax',

            'Total',

            'Created By',
        ];

        $rows = $report['invoices']->map(
            function (Invoice $invoice) use (
                $isSale
            ): array {
                return [
                    $invoice->invoice_number,

                    $isSale
                        ? $invoice->customer?->name
                        : $invoice->supplier?->name,

                    $invoice->invoice_date->format(
                        'Y-m-d'
                    ),

                    $invoice->status->value,

                    $invoice->subtotal,

                    $invoice->discount,

                    $invoice->tax,

                    $invoice->total,

                    $invoice->user->name,
                ];
            }
        );

        $filename = sprintf(
            '%s-report-%s-to-%s.csv',

            $isSale
                ? 'sales'
                : 'purchases',

            $report['from']->format(
                'Y-m-d'
            ),

            $report['to']->format(
                'Y-m-d'
            )
        );

        return $csvExportService->export(
            $filename,
            $headers,
            $rows
        );
    }

    public function exportProfit(
        Request $request,
        ReportService $reportService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        Gate::authorize('view-reports');

        [$from, $to] = $this->resolveDateRange($request);

        $report = $reportService->profitReport(
            $from,
            $to
        );

        $headers = [
            'Product',
            'SKU',
            'Quantity Sold',
            'Revenue',
            'Cost',
            'Profit',
        ];

        $rows = $report['products']->map(
            function (array $product): array {
                return [
                    $product['product_name'],

                    $product['sku'],

                    $product['quantity'],

                    number_format(
                        $product['revenue'] / 100,
                        2,
                        '.',
                        ''
                    ),

                    number_format(
                        $product['cost'] / 100,
                        2,
                        '.',
                        ''
                    ),

                    number_format(
                        $product['profit'] / 100,
                        2,
                        '.',
                        ''
                    ),
                ];
            }
        );

        $filename = sprintf(
            'profit-report-%s-to-%s.csv',
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        );

        return $csvExportService->export(
            $filename,
            $headers,
            $rows
        );
    }

    public function exportStock(
        ReportService $reportService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        Gate::authorize('view-reports');

        $report = $reportService->stockReport();

        $headers = [
            'Product',
            'SKU',
            'Category',
            'Quantity',
            'Reorder Level',
            'Cost Price',
            'Selling Price',
            'Stock Status',
        ];

        $rows = $report['products']->map(
            function ($product): array {
                return [
                    $product->name,

                    $product->sku,

                    $product->category?->name ?? '',

                    $product->quantity,

                    $product->reorder_level,

                    $product->cost_price,

                    $product->sell_price,

                    $product->quantity <= $product->reorder_level
                        ? 'Low Stock'
                        : 'In Stock',
                ];
            }
        );

        $filename = sprintf(
            'stock-report-%s.csv',
            now()->format('Y-m-d')
        );

        return $csvExportService->export(
            $filename,
            $headers,
            $rows
        );
    }

    public function exportCustomerStatement(
        Request $request,
        Customer $customer,
        ReportService $reportService,
        CsvExportService $csvExportService
    ): StreamedResponse {
        Gate::authorize('view-reports');

        [$from, $to] = $this->resolveDateRange($request);

        $report = $reportService->customerStatement(
            $customer,
            $from,
            $to
        );

        $headers = [
            'Date',
            'Type',
            'Reference',
            'Debit',
            'Credit',
        ];

        $invoiceRows = $report['invoices']->map(
            function (Invoice $invoice): array {
                return [
                    'date' => $invoice->invoice_date->format(
                        'Y-m-d'
                    ),

                    'type' => 'Invoice',

                    'reference' => $invoice->invoice_number,

                    'debit' => $invoice->total,

                    'credit' => '0.00',
                ];
            }
        );

        $paymentRows = $report['payments']->map(
            function ($payment): array {
                return [
                    'date' => $payment->paid_at->format(
                        'Y-m-d'
                    ),

                    'type' => 'Payment',

                    'reference' => $payment->reference
                        ?? $payment->payable->invoice_number,

                    'debit' => '0.00',

                    'credit' => $payment->amount,
                ];
            }
        );

        $rows = $invoiceRows
            ->concat($paymentRows)
            ->sortBy('date')
            ->values()
            ->map(
                function (array $row): array {
                    return [
                        $row['date'],
                        $row['type'],
                        $row['reference'],
                        $row['debit'],
                        $row['credit'],
                    ];
                }
            );

        $filename = sprintf(
            'customer-%d-statement-%s-to-%s.csv',
            $customer->id,
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        );

        return $csvExportService->export(
            $filename,
            $headers,
            $rows
        );
    }

    private function resolveDateRange(
        Request $request
    ): array {
        $validated = $request->validate([
            'from' => [
                'nullable',
                'date',
            ],

            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse(
                $validated['from']
            )->startOfDay()
            : now()->startOfMonth();

        $to = isset($validated['to'])
            ? Carbon::parse(
                $validated['to']
            )->endOfDay()
            : now()->endOfDay();

        return [
            $from,
            $to,
        ];
    }

    private function currency(): string
    {
        return (string) Setting::get(
            'currency_symbol',
            'EGP'
        );
    }
}
