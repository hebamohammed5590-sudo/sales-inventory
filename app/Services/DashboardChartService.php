<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardChartService
{
    public function charts(User $user): array
    {
        return [
            'monthly_sales' => $this->monthlySales(),

            'top_products' => $this->topProducts(),

            'sales_by_category' => $this->salesByCategory(),

            'sales_vs_purchases' => $this->salesVsPurchases(
                $user
            ),
        ];
    }

    public function monthlySales(): array
    {
        $months = $this->months(12);

        $invoices = $this->invoiceQuery(
            InvoiceType::Sale,
            now()->startOfMonth()->subMonths(11),
            now()->endOfMonth()
        )->get([
            'invoice_date',
            'total',
        ]);

        $totalsByMonth = $invoices
            ->groupBy(
                fn (Invoice $invoice): string => Carbon::parse(
                    $invoice->invoice_date
                )->format('Y-m')
            )
            ->map(
                fn (Collection $invoices): int => $invoices->sum(
                    fn (Invoice $invoice): int => (int) $invoice->getRawOriginal(
                        'total'
                    )
                )
            );

        return [
            'labels' => $months
                ->map(
                    fn (Carbon $month): string => $month->format(
                        'M Y'
                    )
                )
                ->values()
                ->all(),

            'values' => $months
                ->map(
                    fn (Carbon $month): float => round(
                        (
                            (int) $totalsByMonth->get(
                                $month->format('Y-m'),
                                0
                            )
                        ) / 100,
                        2
                    )
                )
                ->values()
                ->all(),
        ];
    }

    public function topProducts(): array
    {
        $items = InvoiceItem::query()
            ->with('product')
            ->whereHas(
                'invoice',
                function ($query): void {
                    $query
                        ->where(
                            'type',
                            InvoiceType::Sale->value
                        )
                        ->whereIn(
                            'status',
                            $this->activeStatuses()
                        );
                }
            )
            ->get();

        $products = $items
            ->groupBy('product_id')
            ->map(function (Collection $items): array {
                $firstItem = $items->first();

                return [
                    'name' => $firstItem->product?->name
                        ?? 'Unknown product',

                    'quantity' => $items->sum(
                        fn (InvoiceItem $item): int => (int) $item->quantity
                    ),
                ];
            })
            ->sortByDesc('quantity')
            ->take(5)
            ->values();

        return [
            'labels' => $products
                ->pluck('name')
                ->all(),

            'values' => $products
                ->pluck('quantity')
                ->all(),
        ];
    }

    public function salesByCategory(): array
    {
        $items = InvoiceItem::query()
            ->with('product.category')
            ->whereHas(
                'invoice',
                function ($query): void {
                    $query
                        ->where(
                            'type',
                            InvoiceType::Sale->value
                        )
                        ->whereIn(
                            'status',
                            $this->activeStatuses()
                        );
                }
            )
            ->get();

        $categories = $items
            ->groupBy(
                fn (InvoiceItem $item): string => $item->product
                    ?->category
                    ?->name
                    ?? 'Uncategorized'
            )
            ->map(
                fn (Collection $items): float => round(
                    $items->sum(
                        fn (InvoiceItem $item): int => (int) $item->getRawOriginal(
                            'line_total'
                        )
                    ) / 100,
                    2
                )
            )
            ->sortDesc();

        return [
            'labels' => $categories
                ->keys()
                ->values()
                ->all(),

            'values' => $categories
                ->values()
                ->all(),
        ];
    }

    public function salesVsPurchases(
        User $user
    ): array {
        $months = $this->months(6);

        $from = now()
            ->startOfMonth()
            ->subMonths(5);

        $to = now()->endOfMonth();

        $sales = $this->invoiceQuery(
            InvoiceType::Sale,
            $from,
            $to
        )->get([
            'invoice_date',
            'total',
        ]);

        $salesByMonth = $this->groupInvoiceTotalsByMonth(
            $sales
        );

        $purchasesByMonth = collect();

        if ($user->role !== Role::Cashier) {
            $purchases = $this->invoiceQuery(
                InvoiceType::Purchase,
                $from,
                $to
            )->get([
                'invoice_date',
                'total',
            ]);

            $purchasesByMonth = $this->groupInvoiceTotalsByMonth(
                $purchases
            );
        }

        return [
            'labels' => $months
                ->map(
                    fn (Carbon $month): string => $month->format(
                        'M Y'
                    )
                )
                ->values()
                ->all(),

            'sales' => $months
                ->map(
                    fn (Carbon $month): float => round(
                        (
                            (int) $salesByMonth->get(
                                $month->format('Y-m'),
                                0
                            )
                        ) / 100,
                        2
                    )
                )
                ->values()
                ->all(),

            'purchases' => $user->role === Role::Cashier
                ? []
                : $months
                    ->map(
                        fn (Carbon $month): float => round(
                            (
                                (int) $purchasesByMonth->get(
                                    $month->format('Y-m'),
                                    0
                                )
                            ) / 100,
                            2
                        )
                    )
                    ->values()
                    ->all(),

            'show_purchases' => $user->role !== Role::Cashier,
        ];
    }

    private function invoiceQuery(
        InvoiceType $type,
        Carbon $from,
        Carbon $to
    ) {
        return Invoice::query()
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
            );
    }

    private function groupInvoiceTotalsByMonth(
        Collection $invoices
    ): Collection {
        return $invoices
            ->groupBy(
                fn (Invoice $invoice): string => Carbon::parse(
                    $invoice->invoice_date
                )->format('Y-m')
            )
            ->map(
                fn (Collection $invoices): int => $invoices->sum(
                    fn (Invoice $invoice): int => (int) $invoice->getRawOriginal(
                        'total'
                    )
                )
            );
    }

    private function months(
        int $count
    ): Collection {
        return collect(
            range(
                $count - 1,
                0
            )
        )->map(
            fn (int $monthsAgo): Carbon => now()
                ->startOfMonth()
                ->subMonths($monthsAgo)
        );
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
