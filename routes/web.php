<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get(
    '/dashboard',
    [
        DashboardController::class,
        'index',
    ]
)
    ->middleware([
        'auth',
        'verified',
    ])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get(
        '/profile',
        [
            ProfileController::class,
            'edit',
        ]
    )->name('profile.edit');
    Route::get(
        '/activity-logs',
        [
            ActivityLogController::class,
            'index',
        ]
    )->name('activity-logs.index');
    Route::get(
        '/search',
        [
            SearchController::class,
            'index',
        ]
    )->name('search.index');

    Route::patch(
        '/profile',
        [
            ProfileController::class,
            'update',
        ]
    )->name('profile.update');

    Route::resource(
        'users',
        UserController::class
    )->only([
        'index',
        'create',
        'store',
        'edit',
        'update',
    ]);

    Route::resource(
        'categories',
        CategoryController::class
    )->except([
        'show',
    ]);

    Route::get(
        'settings',
        [
            SettingController::class,
            'edit',
        ]
    )->name('settings.edit');

    Route::put(
        'settings',
        [
            SettingController::class,
            'update',
        ]
    )->name('settings.update');

    // مسارات المنتجات (تصدير، استيراد، ثم الـ resource)
    Route::post(
        'products/import',
        [
            ProductController::class,
            'import',
        ]
    )->name('products.import');

    Route::get(
        'products/import/sample',
        [
            ProductController::class,
            'downloadImportSample',
        ]
    )->name('products.import.sample');

    Route::get(
        'products/export',
        [
            ProductController::class,
            'export',
        ]
    )->name('products.export');

    Route::resource(
        'products',
        ProductController::class
    );

    // مسارات العملاء (تصدير ثم الـ resource)
    Route::get(
        'customers/export',
        [
            CustomerController::class,
            'export',
        ]
    )->name('customers.export');

    Route::resource(
        'customers',
        CustomerController::class
    );

    // مسارات الموردين (تصدير ثم الـ resource)
    Route::get(
        'suppliers/export',
        [
            SupplierController::class,
            'export',
        ]
    )->name('suppliers.export');

    Route::resource(
        'suppliers',
        SupplierController::class
    );

    Route::resource(
        'stock-adjustments',
        StockAdjustmentController::class
    )->only([
        'index',
        'create',
        'store',
    ]);

    Route::prefix('reports')
        ->name('reports.')
        ->group(function () {
            Route::get(
                '/',
                [
                    ReportController::class,
                    'index',
                ]
            )->name('index');

            Route::get(
                '/sales',
                [
                    ReportController::class,
                    'sales',
                ]
            )->name('sales');

            Route::get(
                '/sales/export',
                [
                    ReportController::class,
                    'exportSales',
                ]
            )->name('sales.export');

            Route::get(
                '/purchases',
                [
                    ReportController::class,
                    'purchases',
                ]
            )->name('purchases');

            Route::get(
                '/purchases/export',
                [
                    ReportController::class,
                    'exportPurchases',
                ]
            )->name('purchases.export');

            Route::get(
                '/profit',
                [
                    ReportController::class,
                    'profit',
                ]
            )->name('profit');

            Route::get(
                '/profit/export',
                [
                    ReportController::class,
                    'exportProfit',
                ]
            )->name('profit.export');

            Route::get(
                '/stock',
                [
                    ReportController::class,
                    'stock',
                ]
            )->name('stock');

            Route::get(
                '/stock/export',
                [
                    ReportController::class,
                    'exportStock',
                ]
            )->name('stock.export');

            Route::get(
                '/customers/{customer}',
                [
                    ReportController::class,
                    'customerStatement',
                ]
            )->name('customers.statement');

            Route::get(
                '/customers/{customer}/export',
                [
                    ReportController::class,
                    'exportCustomerStatement',
                ]
            )->name('customers.statement.export');
        });

    Route::prefix('invoices/{type}')
        ->whereIn('type', [
            'sale',
            'purchase',
        ])
        ->name('invoices.')
        ->group(function () {
            Route::get(
                '/',
                [
                    InvoiceController::class,
                    'index',
                ]
            )->name('index');

            Route::get(
                '/create',
                [
                    InvoiceController::class,
                    'create',
                ]
            )->name('create');

            // مسار تصدير الفواتير قبل مسار الـ show
            Route::get(
                '/export',
                [
                    InvoiceController::class,
                    'export',
                ]
            )->name('export');

            Route::post(
                '/',
                [
                    InvoiceController::class,
                    'store',
                ]
            )->name('store');

            Route::get(
                '/{invoice}',
                [
                    InvoiceController::class,
                    'show',
                ]
            )->name('show');

            Route::get(
                '/{invoice}/print',
                [
                    InvoiceController::class,
                    'print',
                ]
            )->name('print');

            Route::post(
                '/{invoice}/confirm',
                [
                    InvoiceController::class,
                    'confirm',
                ]
            )->name('confirm');

            Route::post(
                '/{invoice}/cancel',
                [
                    InvoiceController::class,
                    'cancel',
                ]
            )->name('cancel');

            Route::post(
                '/{invoice}/payments',
                [
                    PaymentController::class,
                    'store',
                ]
            )->name('payments.store');
        });
});

Route::post(
    'locale/{locale}',
    [
        LocaleController::class,
        'update',
    ]
)->name('locale.update');

require __DIR__.'/auth.php';
