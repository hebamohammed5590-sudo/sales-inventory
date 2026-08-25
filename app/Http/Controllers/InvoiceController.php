<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\CsvExportService;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(
        Request $request,
        string $type
    ): View {
        $invoiceType = $this->resolveType($type);

        Gate::authorize(
            'create',
            [
                Invoice::class,
                $invoiceType,
            ]
        );

        $query = Invoice::query()
            ->with([
                'customer',
                'supplier',
                'user',
            ])
            ->where(
                'type',
                $invoiceType->value
            );

        if ($request->filled('search')) {
            $query->where(
                'invoice_number',
                'like',
                '%'.$request->string('search').'%'
            );
        }

        if ($request->filled('status')) {
            $status = InvoiceStatus::tryFrom(
                $request->string('status')->toString()
            );

            if ($status !== null) {
                $query->where(
                    'status',
                    $status->value
                );
            }
        }

        $invoices = $query
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $statuses = InvoiceStatus::cases();

        return view(
            'invoices.index',
            compact(
                'invoices',
                'invoiceType',
                'statuses'
            )
        );
    }

    public function export(
        Request $request,
        string $type,
        CsvExportService $csvExportService
    ): StreamedResponse {
        $invoiceType = $this->resolveType(
            $type
        );

        Gate::authorize(
            'create',

            [
                Invoice::class,

                $invoiceType,
            ]
        );

        $query = Invoice::query()

            ->with([
                'customer',
                'supplier',
                'user',
            ])

            ->where(
                'type',
                $invoiceType->value
            );

        if ($request->filled('search')) {
            $search = $request
                ->string('search')
                ->toString();

            $query->where(
                'invoice_number',

                'like',

                "%{$search}%"
            );
        }

        if ($request->filled('status')) {
            $status = InvoiceStatus::tryFrom(
                $request
                    ->string('status')
                    ->toString()
            );

            if ($status !== null) {
                $query->where(
                    'status',
                    $status->value
                );
            }
        }

        $invoices = $query

            ->latest('invoice_date')

            ->latest('id')

            ->get()

            ->map(
                fn (Invoice $invoice): array => [
                    $invoice->invoice_number,

                    $invoice->type->value,

                    $invoice->isSale()
                        ? ($invoice->customer?->name ?? '')
                        : ($invoice->supplier?->name ?? ''),

                    $invoice->invoice_date?->format(
                        'Y-m-d'
                    ) ?? '',

                    (string) $invoice->subtotal,

                    (string) $invoice->discount,

                    (string) $invoice->tax,

                    (string) $invoice->total,

                    $invoice->status->value,

                    $invoice->user?->name ?? '',
                ]
            );

        $filename = $invoiceType === InvoiceType::Sale
            ? 'sales-invoices.csv'
            : 'purchase-invoices.csv';

        return $csvExportService->export(
            $filename,

            [
                'Invoice Number',
                'Type',
                'Customer / Supplier',
                'Invoice Date',
                'Subtotal',
                'Discount',
                'Tax',
                'Total',
                'Status',
                'Created By',
            ],

            $invoices
        );
    }

    public function create(
        string $type
    ): View {
        $invoiceType = $this->resolveType($type);

        Gate::authorize(
            'create',
            [
                Invoice::class,
                $invoiceType,
            ]
        );

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'sku',
                'quantity',
                'cost_price',
                'sell_price',
            ]);

        $customers = $invoiceType === InvoiceType::Sale
            ? Customer::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'phone',
                ])
            : collect();

        $suppliers = $invoiceType === InvoiceType::Purchase
            ? Supplier::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'phone',
                ])
            : collect();
        $taxRate = Setting::get(
            'tax_rate',
            '0'
        );

        return view(
            'invoices.create',
            compact(
                'invoiceType',
                'products',
                'customers',
                'suppliers',
                'taxRate'
            )
        );
    }

    public function store(
        StoreInvoiceRequest $request,
        string $type,
        InvoiceService $invoiceService
    ): RedirectResponse {
        $invoiceType = $this->resolveType($type);

        $invoice = $invoiceService->create(
            type: $invoiceType,
            user: $request->user(),
            data: $request->validated()
        );

        return redirect()
            ->route(
                'invoices.show',
                [
                    'type' => $invoiceType->value,
                    'invoice' => $invoice,
                ]
            )
            ->with(
                'success',
                'Invoice created successfully.'
            );
    }

    public function show(
        string $type,
        Invoice $invoice
    ): View {
        $invoiceType = $this->resolveType($type);

        $this->ensureInvoiceMatchesType(
            $invoice,
            $invoiceType
        );

        Gate::authorize(
            'view',
            $invoice
        );

        $invoice->load([
            'items.product',
            'customer',
            'supplier',
            'user',
            'stockMovements.user',
            'payments.user',
        ]);

        return view(
            'invoices.show',
            compact(
                'invoice',
                'invoiceType'
            )
        );
    }

    public function print(
        string $type,
        Invoice $invoice
    ): View {
        $invoiceType = $this->resolveType($type);

        $this->ensureInvoiceMatchesType(
            $invoice,
            $invoiceType
        );

        Gate::authorize(
            'view',
            $invoice
        );

        $invoice->load([
            'items.product',
            'customer',
            'supplier',
            'user',
            'payments.user',
        ]);

        $companyName = Setting::get(
            'company_name',
            'Sales Inventory'
        );

        $companyPhone = Setting::get(
            'company_phone',
            ''
        );

        $companyAddress = Setting::get(
            'company_address',
            ''
        );

        $currencySymbol = Setting::get(
            'currency_symbol',
            'EGP'
        );

        return view(
            'invoices.print',
            compact(
                'invoice',
                'invoiceType',
                'companyName',
                'companyPhone',
                'companyAddress',
                'currencySymbol'
            )
        );
    }

    public function confirm(
        Request $request,
        string $type,
        Invoice $invoice,
        InvoiceService $invoiceService
    ): RedirectResponse {
        $invoiceType = $this->resolveType($type);

        $this->ensureInvoiceMatchesType(
            $invoice,
            $invoiceType
        );

        Gate::authorize(
            'confirm',
            $invoice
        );

        $invoiceService->confirm(
            invoice: $invoice,
            user: $request->user()
        );

        return redirect()
            ->route(
                'invoices.show',
                [
                    'type' => $invoiceType->value,
                    'invoice' => $invoice,
                ]
            )
            ->with(
                'success',
                'Invoice confirmed successfully.'
            );
    }

    public function cancel(
        Request $request,
        string $type,
        Invoice $invoice,
        InvoiceService $invoiceService
    ): RedirectResponse {
        $invoiceType = $this->resolveType($type);

        $this->ensureInvoiceMatchesType(
            $invoice,
            $invoiceType
        );

        Gate::authorize(
            'cancel',
            $invoice
        );

        $invoiceService->cancel(
            invoice: $invoice,
            user: $request->user()
        );

        return redirect()
            ->route(
                'invoices.show',
                [
                    'type' => $invoiceType->value,
                    'invoice' => $invoice,
                ]
            )
            ->with(
                'success',
                'Invoice cancelled successfully.'
            );
    }

    private function resolveType(
        string $type
    ): InvoiceType {
        $invoiceType = InvoiceType::tryFrom($type);

        abort_if(
            $invoiceType === null,
            404
        );

        return $invoiceType;
    }

    private function ensureInvoiceMatchesType(
        Invoice $invoice,
        InvoiceType $type
    ): void {
        abort_unless(
            $invoice->type === $type,
            404
        );
    }
}
