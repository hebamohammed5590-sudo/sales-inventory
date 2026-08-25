<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public function salesReport(
        Carbon $from,
        Carbon $to
    ): array {
        return $this->invoiceReport(
            InvoiceType::Sale,
            $from,
            $to
        );
    }

    public function purchasesReport(
        Carbon $from,
        Carbon $to
    ): array {
        return $this->invoiceReport(
            InvoiceType::Purchase,
            $from,
            $to
        );
    }

    public function profitReport(
        Carbon $from,
        Carbon $to
    ): array {
        $items = InvoiceItem::query()
            ->whereHas(
                'invoice',
                function (Builder $query) use (
                    $from,
                    $to
                ): void {
                    $query
                        ->where(
                            'type',
                            InvoiceType::Sale->value
                        )
                        ->whereIn(
                            'status',
                            $this->activeStatuses()
                        )
                        ->whereDate(
                            'invoice_date',
                            '>=',
                            $from->toDateString()
                        )
                        ->whereDate(
                            'invoice_date',
                            '<=',
                            $to->toDateString()
                        );
                }
            )
            ->with([
                'product',
                'invoice',
            ])
            ->get();

        $revenue = 0;
        $cost = 0;

        $products = $items
            ->groupBy('product_id')
            ->map(function (
                Collection $productItems
            ) use (
                &$revenue,
                &$cost
            ): array {
                $product = $productItems
                    ->first()
                    ->product;

                $quantity = (int) $productItems
                    ->sum('quantity');

                $productRevenue = $productItems->sum(
                    function (InvoiceItem $item): int {
                        return (int) $item->getRawOriginal(
                            'line_total'
                        );
                    }
                );

                $unitCost = (int) $product->getRawOriginal(
                    'cost_price'
                );

                $productCost = $quantity * $unitCost;

                $revenue += $productRevenue;

                $cost += $productCost;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'quantity' => $quantity,
                    'revenue' => $productRevenue,
                    'cost' => $productCost,
                    'profit' => $productRevenue - $productCost,
                ];
            })
            ->values();

        return [
            'from' => $from,
            'to' => $to,
            'products' => $products,
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $revenue - $cost,
        ];
    }

    public function stockReport(): array
    {
        $products = Product::query()
            ->with('category')
            ->orderBy('name')
            ->get();

        $totalCostValue = 0;
        $totalSellingValue = 0;

        foreach ($products as $product) {
            $costPrice = (int) $product->getRawOriginal(
                'cost_price'
            );

            $sellingPrice = (int) $product->getRawOriginal(
                'sell_price'
            );

            $totalCostValue += $product->quantity * $costPrice;
            $totalSellingValue += $product->quantity * $sellingPrice;
        }

        return [
            'products' => $products,
            'products_count' => $products->count(),
            'total_quantity' => (int) $products->sum(
                'quantity'
            ),
            'low_stock_count' => $products
                ->filter(function (Product $product): bool {
                    return $product->quantity
                        <= $product->reorder_level;
                })
                ->count(),
            'total_cost_value' => $totalCostValue,
            'total_selling_value' => $totalSellingValue,
        ];
    }

    public function customerStatement(
        Customer $customer,
        Carbon $from,
        Carbon $to
    ): array {
        $invoices = Invoice::query()
            ->where(
                'type',
                InvoiceType::Sale->value
            )
            ->where(
                'customer_id',
                $customer->id
            )
            ->whereIn(
                'status',
                $this->activeStatuses()
            )
            ->whereDate(
                'invoice_date',
                '>=',
                $from->toDateString()
            )
            ->whereDate(
                'invoice_date',
                '<=',
                $to->toDateString()
            )
            ->with('payments')
            ->orderBy('invoice_date')
            ->get();

        $invoiceIds = $invoices
            ->pluck('id');

        $payments = Payment::query()
            ->where(
                'payable_type',
                Invoice::class
            )
            ->whereIn(
                'payable_id',
                $invoiceIds
            )
            ->whereBetween(
                'paid_at',
                [
                    $from->copy()->startOfDay(),
                    $to->copy()->endOfDay(),
                ]
            )
            ->with([
                'payable',
                'user',
            ])
            ->orderBy('paid_at')
            ->get();

        $totalInvoiced = $invoices->sum(
            function (Invoice $invoice): int {
                return (int) $invoice->getRawOriginal(
                    'total'
                );
            }
        );

        $totalPaid = $payments->sum(
            function (Payment $payment): int {
                return (int) $payment->getRawOriginal(
                    'amount'
                );
            }
        );

        return [
            'customer' => $customer,
            'from' => $from,
            'to' => $to,
            'invoices' => $invoices,
            'payments' => $payments,
            'total_invoiced' => $totalInvoiced,
            'total_paid' => $totalPaid,
            'balance' => $totalInvoiced - $totalPaid,
        ];
    }

    private function invoiceReport(
        InvoiceType $type,
        Carbon $from,
        Carbon $to
    ): array {
        $invoices = Invoice::query()
            ->where(
                'type',
                $type->value
            )
            ->whereIn(
                'status',
                $this->activeStatuses()
            )
            ->whereDate(
                'invoice_date',
                '>=',
                $from->toDateString()
            )
            ->whereDate(
                'invoice_date',
                '<=',
                $to->toDateString()
            )
            ->with([
                'customer',
                'supplier',
                'user',
            ])
            ->orderByDesc('invoice_date')
            ->get();

        $total = $invoices->sum(
            function (Invoice $invoice): int {
                return (int) $invoice->getRawOriginal(
                    'total'
                );
            }
        );

        $dailyTotals = $invoices
            ->groupBy(
                function (Invoice $invoice): string {
                    return $invoice->invoice_date
                        ->toDateString();
                }
            )
            ->map(
                function (
                    Collection $dayInvoices
                ): array {
                    return [
                        'count' => $dayInvoices->count(),
                        'total' => $dayInvoices->sum(
                            function (
                                Invoice $invoice
                            ): int {
                                return (int) $invoice->getRawOriginal(
                                    'total'
                                );
                            }
                        ),
                    ];
                }
            )
            ->sortKeys();

        return [
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'invoices' => $invoices,
            'count' => $invoices->count(),
            'total' => $total,
            'daily_totals' => $dailyTotals,
        ];
    }

    private function activeStatuses(): array
    {
        return [
            InvoiceStatus::Confirmed->value,
            InvoiceStatus::PartiallyPaid->value,
            InvoiceStatus::Paid->value,
        ];
    }
}
