<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;

    private PaymentService $paymentService;

    private ReportService $reportService;

    private User $user;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceService = app(
            InvoiceService::class
        );

        $this->paymentService = app(
            PaymentService::class
        );

        $this->reportService = app(
            ReportService::class
        );

        Setting::set(
            'tax_rate',
            '0'
        );

        $this->user = User::factory()->create();

        $this->customer = Customer::factory()->create();

        $this->supplier = Supplier::factory()->create();

        $this->product = Product::factory()->create([
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 10,
            'reorder_level' => 5,
            'is_active' => true,
        ]);
    }

    public function test_sales_report_includes_confirmed_sales(): void
    {
        $invoice = $this->createConfirmedInvoice(
            InvoiceType::Sale,
            2
        );

        $report = $this->reportService->salesReport(
            today(),
            today()
        );

        $this->assertSame(
            1,
            $report['count']
        );

        $this->assertSame(
            20000,
            $report['total']
        );

        $this->assertTrue(
            $report['invoices']->contains(
                'id',
                $invoice->id
            )
        );
    }

    public function test_purchases_report_includes_confirmed_purchases(): void
    {
        $invoice = $this->createConfirmedInvoice(
            InvoiceType::Purchase,
            3
        );

        $report = $this->reportService->purchasesReport(
            today(),
            today()
        );

        $this->assertSame(
            1,
            $report['count']
        );

        $this->assertSame(
            15000,
            $report['total']
        );

        $this->assertTrue(
            $report['invoices']->contains(
                'id',
                $invoice->id
            )
        );
    }

    public function test_sales_report_excludes_draft_invoices(): void
    {
        $this->createDraftInvoice(
            InvoiceType::Sale,
            2
        );

        $report = $this->reportService->salesReport(
            today(),
            today()
        );

        $this->assertSame(
            0,
            $report['count']
        );

        $this->assertSame(
            0,
            $report['total']
        );
    }

    public function test_sales_report_excludes_cancelled_invoices(): void
    {
        $invoice = $this->createConfirmedInvoice(
            InvoiceType::Sale,
            2
        );

        $this->invoiceService->cancel(
            $invoice,
            $this->user
        );

        $report = $this->reportService->salesReport(
            today(),
            today()
        );

        $this->assertSame(
            0,
            $report['count']
        );

        $this->assertSame(
            0,
            $report['total']
        );
    }

    public function test_reports_filter_invoices_by_date_range(): void
    {
        $this->createConfirmedInvoice(
            InvoiceType::Sale,
            2,
            today()->subDays(10)
        );

        $this->createConfirmedInvoice(
            InvoiceType::Sale,
            1,
            today()
        );

        $report = $this->reportService->salesReport(
            today()->subDays(2),
            today()
        );

        $this->assertSame(
            1,
            $report['count']
        );

        $this->assertSame(
            10000,
            $report['total']
        );
    }

    public function test_sales_report_groups_totals_by_day(): void
    {
        $this->createConfirmedInvoice(
            InvoiceType::Sale,
            1,
            today()->subDay()
        );

        $this->createConfirmedInvoice(
            InvoiceType::Sale,
            2,
            today()
        );

        $report = $this->reportService->salesReport(
            today()->subDay(),
            today()
        );

        $yesterday = today()->subDay()->toDateString();

        $today = today()->toDateString();

        $this->assertSame(
            10000,
            $report['daily_totals'][$yesterday]['total']
        );

        $this->assertSame(
            20000,
            $report['daily_totals'][$today]['total']
        );
    }

    public function test_profit_report_calculates_revenue_cost_and_profit(): void
    {
        $this->createConfirmedInvoice(
            InvoiceType::Sale,
            3
        );

        $report = $this->reportService->profitReport(
            today(),
            today()
        );

        $this->assertSame(
            30000,
            $report['revenue']
        );

        $this->assertSame(
            15000,
            $report['cost']
        );

        $this->assertSame(
            15000,
            $report['profit']
        );

        $this->assertCount(
            1,
            $report['products']
        );
    }

    public function test_stock_report_calculates_inventory_values(): void
    {
        $report = $this->reportService->stockReport();

        $this->assertSame(
            1,
            $report['products_count']
        );

        $this->assertSame(
            10,
            $report['total_quantity']
        );

        $this->assertSame(
            50000,
            $report['total_cost_value']
        );

        $this->assertSame(
            100000,
            $report['total_selling_value']
        );
    }

    public function test_stock_report_counts_low_stock_products(): void
    {
        $this->product->forceFill([
            'quantity' => 4,
        ])->save();

        $report = $this->reportService->stockReport();

        $this->assertSame(
            1,
            $report['low_stock_count']
        );
    }

    public function test_customer_statement_includes_invoices_and_payments(): void
    {
        $invoice = $this->createConfirmedInvoice(
            InvoiceType::Sale,
            2
        );

        $this->paymentService->create(
            $invoice,
            $this->user,
            [
                'amount' => '75.00',
                'method' => PaymentMethod::Cash,
            ]
        );

        $statement = $this->reportService->customerStatement(
            $this->customer,
            today(),
            today()
        );

        $this->assertCount(
            1,
            $statement['invoices']
        );

        $this->assertCount(
            1,
            $statement['payments']
        );

        $this->assertSame(
            20000,
            $statement['total_invoiced']
        );

        $this->assertSame(
            7500,
            $statement['total_paid']
        );

        $this->assertSame(
            12500,
            $statement['balance']
        );
    }

    private function createDraftInvoice(
        InvoiceType $type,
        int $quantity,
        ?Carbon $date = null
    ): Invoice {
        $data = [
            'invoice_date' => ($date ?? today())
                ->toDateString(),

            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => $quantity,
                ],
            ],
        ];

        if ($type === InvoiceType::Sale) {
            $data['customer_id'] = $this->customer->id;
        } else {
            $data['supplier_id'] = $this->supplier->id;
        }

        return $this->invoiceService->create(
            $type,
            $this->user,
            $data
        );
    }

    private function createConfirmedInvoice(
        InvoiceType $type,
        int $quantity,
        ?Carbon $date = null
    ): Invoice {
        $invoice = $this->createDraftInvoice(
            $type,
            $quantity,
            $date
        );

        return $this->invoiceService->confirm(
            $invoice,
            $this->user
        );
    }
}
