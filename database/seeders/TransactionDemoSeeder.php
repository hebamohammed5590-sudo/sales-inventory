<?php

namespace Database\Seeders;

use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;

class TransactionDemoSeeder extends Seeder
{
    private const PURCHASE_TARGET = 40;

    private const SALE_TARGET = 120;

    private const PURCHASE_NOTE = 'Demo purchase invoice';

    private const SALE_NOTE = 'Demo sales invoice';

    private InvoiceService $invoiceService;

    private PaymentService $paymentService;

    private User $user;

    private Collection $products;

    private Collection $customers;

    private Collection $suppliers;

    public function run(): void
    {
        $this->invoiceService = app(
            InvoiceService::class
        );

        $this->paymentService = app(
            PaymentService::class
        );

        $this->user = User::query()
            ->where(
                'email',
                'manager@example.com'
            )
            ->firstOrFail();

        $this->products = Product::query()
            ->where(
                'is_active',
                true
            )
            ->get();

        $this->customers = Customer::query()->get();

        $this->suppliers = Supplier::query()->get();

        $this->ensureRequiredDataExists();

        $this->createPurchaseInvoices();

        $this->createSalesInvoices();
    }

    private function ensureRequiredDataExists(): void
    {
        if ($this->products->isEmpty()) {
            throw new RuntimeException(
                'No active products found. Run DemoDataSeeder first.'
            );
        }

        if ($this->customers->isEmpty()) {
            throw new RuntimeException(
                'No customers found. Run DemoDataSeeder first.'
            );
        }

        if ($this->suppliers->isEmpty()) {
            throw new RuntimeException(
                'No suppliers found. Run DemoDataSeeder first.'
            );
        }
    }

    private function createPurchaseInvoices(): void
    {
        $existingCount = Invoice::query()
            ->where(
                'type',
                InvoiceType::Purchase->value
            )
            ->where(
                'notes',
                self::PURCHASE_NOTE
            )
            ->count();

        $missingCount = max(
            0,
            self::PURCHASE_TARGET - $existingCount
        );

        if ($missingCount === 0) {
            $this->command?->info(
                'Demo purchase invoices already exist.'
            );

            return;
        }

        for (
            $index = 0;
            $index < $missingCount;
            $index++
        ) {
            $invoiceDate = $this->randomInvoiceDate();

            $supplier = $this->suppliers->random();

            $products = $this->selectPurchaseProducts(
                $existingCount + $index
            );

            $items = $products
                ->map(
                    fn (Product $product): array => [
                        'product_id' => $product->id,

                        'quantity' => random_int(
                            20,
                            50
                        ),
                    ]
                )
                ->values()
                ->all();

            $invoice = $this->invoiceService->create(
                InvoiceType::Purchase,

                $this->user,

                [
                    'supplier_id' => $supplier->id,

                    'invoice_date' => $invoiceDate,

                    'notes' => self::PURCHASE_NOTE,

                    'items' => $items,
                ]
            );

            $confirmedInvoice = $this->invoiceService->confirm(
                $invoice,

                $this->user
            );

            $this->createRandomPayments(
                $confirmedInvoice,

                $invoiceDate
            );
        }

        $this->command?->info(
            sprintf(
                '%d demo purchase invoices created.',

                $missingCount
            )
        );
    }

    private function createSalesInvoices(): void
    {
        $existingCount = Invoice::query()
            ->where(
                'type',
                InvoiceType::Sale->value
            )
            ->where(
                'notes',
                self::SALE_NOTE
            )
            ->count();

        $missingCount = max(
            0,
            self::SALE_TARGET - $existingCount
        );

        if ($missingCount === 0) {
            $this->command?->info(
                'Demo sales invoices already exist.'
            );

            return;
        }

        $createdCount = 0;

        for (
            $index = 0;
            $index < $missingCount;
            $index++
        ) {
            $availableProducts = Product::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity',
                    '>',
                    0
                )
                ->get();

            if ($availableProducts->isEmpty()) {
                $this->command?->warn(
                    'No products with available stock remain.'
                );

                break;
            }

            $invoiceDate = $this->randomInvoiceDate();

            $customer = $this->customers->random();

            $selectedProducts = $availableProducts
                ->shuffle()
                ->take(
                    min(
                        random_int(
                            1,
                            3
                        ),

                        $availableProducts->count()
                    )
                );

            $items = $selectedProducts
                ->map(
                    fn (Product $product): array => [
                        'product_id' => $product->id,

                        'quantity' => random_int(
                            1,

                            min(
                                5,

                                (int) $product->quantity
                            )
                        ),
                    ]
                )
                ->values()
                ->all();

            $invoice = $this->invoiceService->create(
                InvoiceType::Sale,

                $this->user,

                [
                    'customer_id' => $customer->id,

                    'invoice_date' => $invoiceDate,

                    'notes' => self::SALE_NOTE,

                    'items' => $items,
                ]
            );

            $confirmedInvoice = $this->invoiceService->confirm(
                $invoice,

                $this->user
            );

            $this->createRandomPayments(
                $confirmedInvoice,

                $invoiceDate
            );

            $createdCount++;
        }

        $this->command?->info(
            sprintf(
                '%d demo sales invoices created.',

                $createdCount
            )
        );
    }

    private function selectPurchaseProducts(
        int $index
    ): Collection {
        $productCount = $this->products->count();

        $guaranteedProduct = $this->products->get(
            $index % $productCount
        );

        $additionalProducts = $this->products
            ->reject(
                fn (Product $product): bool => $product->id
                    === $guaranteedProduct->id
            )
            ->shuffle()
            ->take(
                min(
                    random_int(
                        1,
                        3
                    ),

                    max(
                        0,

                        $productCount - 1
                    )
                )
            );

        return collect([
            $guaranteedProduct,
        ])->merge(
            $additionalProducts
        );
    }

    private function createRandomPayments(
        Invoice $invoice,

        Carbon $invoiceDate
    ): void {
        $paymentScenario = random_int(
            1,
            4
        );

        if ($paymentScenario === 1) {
            return;
        }

        $totalInCents = (int) $invoice->getRawOriginal(
            'total'
        );

        if ($totalInCents <= 0) {
            return;
        }

        if ($paymentScenario === 2) {
            $partialAmount = max(
                1,

                intdiv(
                    $totalInCents,

                    2
                )
            );

            if ($partialAmount >= $totalInCents) {
                return;
            }

            $this->createPayment(
                $invoice,

                $partialAmount,

                $invoiceDate
            );

            return;
        }

        if ($paymentScenario === 3) {
            $this->createPayment(
                $invoice,

                $totalInCents,

                $invoiceDate
            );

            return;
        }

        $firstAmount = intdiv(
            $totalInCents,

            2
        );

        if ($firstAmount < 1) {
            $this->createPayment(
                $invoice,

                $totalInCents,

                $invoiceDate
            );

            return;
        }

        $this->createPayment(
            $invoice,

            $firstAmount,

            $invoiceDate
        );

        $remainingAmount = $invoice
            ->fresh()
            ->remainingAmountInCents();

        if ($remainingAmount > 0) {
            $this->createPayment(
                $invoice->fresh(),

                $remainingAmount,

                $invoiceDate
            );
        }
    }

    private function createPayment(
        Invoice $invoice,

        int $amountInCents,

        Carbon $invoiceDate
    ): void {
        $methods = PaymentMethod::cases();

        $method = $methods[
            array_rand(
                $methods
            )
        ];

        $this->paymentService->create(
            $invoice,

            $this->user,

            [
                'amount' => $this->formatMoney(
                    $amountInCents
                ),

                'method' => $method,

                'paid_at' => $invoiceDate,

                'notes' => 'Demo payment',
            ]
        );
    }

    private function randomInvoiceDate(): Carbon
    {
        $start = now()
            ->startOfDay()
            ->subMonths(11);

        $end = now();

        return Carbon::createFromTimestamp(
            random_int(
                $start->timestamp,

                $end->timestamp
            )
        );
    }

    private function formatMoney(
        int $amountInCents
    ): string {
        return sprintf(
            '%d.%02d',

            intdiv(
                $amountInCents,

                100
            ),

            $amountInCents % 100
        );
    }
}
