<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Services\DashboardChartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        DashboardChartService $dashboardChartService
    ): View {
        $user = $request->user();
        $charts = $dashboardChartService->charts(
            $user
        );

        $cacheKey = sprintf(
            'dashboard.data.%s',
            $user->role->value
        );

        $dashboard = Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            function () use ($user): array {
                $activeStatuses = [
                    InvoiceStatus::Confirmed->value,

                    InvoiceStatus::PartiallyPaid->value,

                    InvoiceStatus::Paid->value,
                ];

                $todaySales = Invoice::query()
                    ->where(
                        'type',
                        InvoiceType::Sale->value
                    )
                    ->whereIn(
                        'status',
                        $activeStatuses
                    )
                    ->whereDate(
                        'invoice_date',
                        today()
                    )
                    ->sum('total');

                $monthlySales = Invoice::query()
                    ->where(
                        'type',
                        InvoiceType::Sale->value
                    )
                    ->whereIn(
                        'status',
                        $activeStatuses
                    )
                    ->whereBetween(
                        'invoice_date',
                        [
                            now()->startOfMonth(),
                            now()->endOfMonth(),
                        ]
                    )
                    ->sum('total');

                $inventoryValue = Product::query()
                    ->selectRaw(
                        'COALESCE(SUM(quantity * cost_price), 0) as total_value'
                    )
                    ->value('total_value');

                $todayInvoices = Invoice::query()
                    ->whereIn(
                        'status',
                        $activeStatuses
                    )
                    ->whereDate(
                        'invoice_date',
                        today()
                    )
                    ->when(
                        $user->role === Role::Cashier,
                        function ($query): void {
                            $query->where(
                                'type',
                                InvoiceType::Sale->value
                            );
                        }
                    )
                    ->count();

                $totalCustomers = Customer::query()
                    ->count();

                $lowStockProducts = Product::query()
                    ->where('is_active', true)
                    ->whereColumn(
                        'quantity',
                        '<=',
                        'reorder_level'
                    )
                    ->orderBy('quantity')
                    ->limit(10)
                    ->get([
                        'id',
                        'name',
                        'sku',
                        'quantity',
                        'reorder_level',
                    ]);

                $latestInvoices = Invoice::query()
                    ->with([
                        'customer',
                        'supplier',
                        'user',
                    ])
                    ->withSum(
                        'payments',
                        'amount'
                    )
                    ->when(
                        $user->role === Role::Cashier,
                        function ($query): void {
                            $query->where(
                                'type',
                                InvoiceType::Sale->value
                            );
                        }
                    )
                    ->latest()
                    ->limit(10)
                    ->get();

                $unpaidInvoices = Invoice::query()
                    ->with([
                        'customer',
                        'supplier',
                    ])
                    ->withSum(
                        'payments',
                        'amount'
                    )
                    ->whereIn(
                        'status',
                        [
                            InvoiceStatus::Confirmed->value,

                            InvoiceStatus::PartiallyPaid->value,
                        ]
                    )
                    ->when(
                        $user->role === Role::Cashier,
                        function ($query): void {
                            $query->where(
                                'type',
                                InvoiceType::Sale->value
                            );
                        }
                    )
                    ->latest('invoice_date')
                    ->limit(10)
                    ->get();

                return [
                    'today_sales' => (int) $todaySales,

                    'monthly_sales' => (int) $monthlySales,

                    'inventory_value' => (int) $inventoryValue,

                    'today_invoices' => $todayInvoices,

                    'total_customers' => $totalCustomers,

                    'low_stock_products' => $lowStockProducts,

                    'latest_invoices' => $latestInvoices,

                    'unpaid_invoices' => $unpaidInvoices,
                ];
            }
        );

        $currency = Setting::get(
            'currency_symbol',
            'EGP'
        );

        return view(
            'dashboard',
            compact(
                'dashboard',
                'currency',
                'charts'
            )
        );
    }
}
