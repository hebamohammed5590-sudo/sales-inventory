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
        $monthlyTotals = $this->monthlyInvoiceTotals(
            $user->role !== Role::Cashier
        );

        return [
            'monthly_sales' => $this->buildMonthlySales(
                $monthlyTotals
            ),

            'top_products' => $this->topProducts(),

            'sales_by_category' => $this->salesByCategory(),

            'sales_vs_purchases' => $this->buildSalesVsPurchases(
                $user,
                $monthlyTotals
            ),
        ];
    }

    public function monthlySales(): array
    {
        return $this->buildMonthlySales(
            $this->monthlyInvoiceTotals(false)
        );
    }

    public function topProducts(): array
    {
        $products = InvoiceItem::query()
            ->join(
                'invoices',
                'invoice_items.invoice_id',
                '=',
                'invoices.id'
            )
            ->leftJoin(
                'products',
                'invoice_items.product_id',
                '=',
                'products.id'
            )
            ->where(
                'invoices.type',
                InvoiceType::Sale->value
            )
            ->whereIn(
                'invoices.status',
                $this->activeStatuses()
            )
            ->selectRaw(
                '
                    invoice_items.product_id,
                    products.name,
                    SUM(invoice_items.quantity) as quantity
                '
            )
            ->groupBy(
                'invoice_items.product_id',
                'products.name'
            )
            ->orderByDesc('quantity')
            ->limit(5)
            ->get();

        return [
            'labels' => $products
                ->map(
                    fn (InvoiceItem $item): string => $item->name
                        ?? 'Unknown product'
                )
                ->values()
                ->all(),

            'values' => $products
                ->map(
                    fn (InvoiceItem $item): int => (int) $item->quantity
                )
                ->values()
                ->all(),
        ];
    }

    public function salesByCategory(): array
    {
        $categories = InvoiceItem::query()
            ->join(
                'invoices',
                'invoice_items.invoice_id',
                '=',
                'invoices.id'
            )
            ->leftJoin(
                'products',
                'invoice_items.product_id',
                '=',
                'products.id'
            )
            ->leftJoin(
                'categories',
                'products.category_id',
                '=',
                'categories.id'
            )
            ->where(
                'invoices.type',
                InvoiceType::Sale->value
            )
            ->whereIn(
                'invoices.status',
                $this->activeStatuses()
            )
            ->selectRaw(
                '
                    categories.id as category_id,
                    categories.name as category_name,
                    SUM(invoice_items.line_total) as total
                '
            )
            ->groupBy(
                'categories.id',
                'categories.name'
            )
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $categories
                ->map(
                    fn (InvoiceItem $item): string => $item->category_name
                        ?? 'Uncategorized'
                )
                ->values()
                ->all(),

            'values' => $categories
                ->map(
                    fn (InvoiceItem $item): float => round(
                        ((int) $item->total) / 100,
                        2
                    )
                )
                ->values()
                ->all(),
        ];
    }

    public function salesVsPurchases(
        User $user
    ): array {
        return $this->buildSalesVsPurchases(
            $user,
            $this->monthlyInvoiceTotals(
                $user->role !== Role::Cashier
            )
        );
    }

    private function monthlyInvoiceTotals(
        bool $includePurchases
    ): Collection {
        return Invoice::query()
            ->whereIn(
                'status',
                $this->activeStatuses()
            )
            ->where(
                function ($query) use ($includePurchases): void {
                    $query->where(
                        'type',
                        InvoiceType::Sale->value
                    );

                    if ($includePurchases) {
                        $query->orWhere(
                            'type',
                            InvoiceType::Purchase->value
                        );
                    }
                }
            )
            ->selectRaw(
                '
                    SUBSTR(invoice_date, 1, 7) as month,
                    type,
                    SUM(total) as total
                '
            )
            ->groupByRaw(
                'SUBSTR(invoice_date, 1, 7), type'
            )
            ->toBase()
            ->get()
            ->mapWithKeys(
                function ($row): array {
                    $type = is_object($row) ? $row->type : $row['type'];
                    $month = is_object($row) ? $row->month : $row['month'];
                    $total = is_object($row) ? $row->total : $row['total'];

                    if ($type instanceof InvoiceType) {
                        $type = $type->value;
                    }

                    return [
                        $type.'.'.$month => (int) $total,
                    ];
                }
            );
    }

    private function buildMonthlySales(
        Collection $monthlyTotals
    ): array {
        $months = $this->months(12);

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
                            (int) $monthlyTotals->get(
                                InvoiceType::Sale->value
                                    .'.'
                                    .$month->format('Y-m'),
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

    private function buildSalesVsPurchases(
        User $user,
        Collection $monthlyTotals
    ): array {
        $months = $this->months(6);

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
                            (int) $monthlyTotals->get(
                                InvoiceType::Sale->value
                                    .'.'
                                    .$month->format('Y-m'),
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
                                (int) $monthlyTotals->get(
                                    InvoiceType::Purchase->value
                                        .'.'
                                        .$month->format('Y-m'),
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
