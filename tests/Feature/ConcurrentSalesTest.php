<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\Role;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ConcurrentSalesTest extends TestCase
{
    private string $originalConnection;

    private User $manager;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = config(
            'database.default'
        );

        config([
            'database.default' => 'concurrency',
        ]);

        DB::purge(
            'concurrency'
        );

        $databaseName = DB::connection(
            'concurrency'
        )->getDatabaseName();

        $this->assertSame(
            'sales_inventory_concurrency_test',
            $databaseName,
            'The concurrency test must use its dedicated test database.'
        );

        Artisan::call(
            'migrate:fresh',
            [
                '--database' => 'concurrency',
                '--force' => true,
            ]
        );

        $this->manager = User::factory()->create([
            'role' => Role::Manager,
            'is_active' => true,
        ]);

        $this->customer = Customer::factory()->create();

        $this->product = Product::factory()->create([
            'name' => 'Concurrent Test Product',
            'cost_price' => '50.00',
            'sell_price' => '100.00',
            'quantity' => 5,
            'reorder_level' => 0,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        config([
            'database.default' => $this->originalConnection,
        ]);

        DB::purge(
            'concurrency'
        );

        parent::tearDown();
    }

    public function test_concurrent_sales_cannot_oversell_available_stock(): void
    {
        $firstInvoice = $this->createDraftInvoice(
            4
        );

        $secondInvoice = $this->createDraftInvoice(
            4
        );

        $firstProcess = $this->confirmationProcess(
            $firstInvoice->id
        );

        $secondProcess = $this->confirmationProcess(
            $secondInvoice->id
        );

        $firstProcess->start();

        $secondProcess->start();

        $firstProcess->wait();

        $secondProcess->wait();

        $this->assertTrue(
            $firstProcess->isSuccessful(),
            sprintf(
                "First process failed:\n%s\n%s",
                $firstProcess->getOutput(),
                $firstProcess->getErrorOutput()
            )
        );

        $this->assertTrue(
            $secondProcess->isSuccessful(),
            sprintf(
                "Second process failed:\n%s\n%s",
                $secondProcess->getOutput(),
                $secondProcess->getErrorOutput()
            )
        );

        $outputs = [
            $firstProcess->getOutput(),
            $secondProcess->getOutput(),
        ];

        $successfulConfirmations = count(
            array_filter(
                $outputs,
                fn (string $output): bool => str_contains(
                    $output,
                    'CONFIRMED_SUCCESSFULLY'
                )
            )
        );

        $rejectedConfirmations = count(
            array_filter(
                $outputs,
                fn (string $output): bool => str_contains(
                    $output,
                    'INSUFFICIENT_STOCK'
                )
            )
        );

        $this->assertSame(
            1,
            $successfulConfirmations,
            'Exactly one concurrent sale must succeed.'
        );

        $this->assertSame(
            1,
            $rejectedConfirmations,
            'Exactly one concurrent sale must be rejected.'
        );

        $this->product->refresh();

        $this->assertSame(
            1,
            $this->product->quantity,
            'Stock must decrease once from 5 to 1.'
        );

        $this->assertGreaterThanOrEqual(
            0,
            $this->product->quantity,
            'Stock must never become negative.'
        );

        $confirmedInvoices = Invoice::query()
            ->whereIn(
                'id',
                [
                    $firstInvoice->id,
                    $secondInvoice->id,
                ]
            )
            ->where(
                'status',
                InvoiceStatus::Confirmed->value
            )
            ->count();

        $draftInvoices = Invoice::query()
            ->whereIn(
                'id',
                [
                    $firstInvoice->id,
                    $secondInvoice->id,
                ]
            )
            ->where(
                'status',
                InvoiceStatus::Draft->value
            )
            ->count();

        $this->assertSame(
            1,
            $confirmedInvoices
        );

        $this->assertSame(
            1,
            $draftInvoices
        );

        $this->assertSame(
            1,
            $this->product
                ->stockMovements()
                ->count()
        );
    }

    private function createDraftInvoice(
        int $quantity
    ): Invoice {
        return app(
            InvoiceService::class
        )->create(
            InvoiceType::Sale,
            $this->manager,
            [
                'customer_id' => $this->customer->id,

                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => $quantity,
                    ],
                ],
            ]
        );
    }

    private function confirmationProcess(
        int $invoiceId
    ): Process {
        $productId = $this->product->id;

        $userId = $this->manager->id;

        $code = sprintf(
            <<<'PHP'
            config(['database.default' => 'concurrency']);

            try {
                \Illuminate\Support\Facades\DB::transaction(
                    function () {
                        \App\Models\Product::query()
                            ->lockForUpdate()
                            ->findOrFail(%d);

                        usleep(750000);

                        $invoice = \App\Models\Invoice::query()
                            ->findOrFail(%d);

                        $user = \App\Models\User::query()
                            ->findOrFail(%d);

                        app(\App\Services\InvoiceService::class)
                            ->confirm($invoice, $user);

                        echo 'CONFIRMED_SUCCESSFULLY';
                    }
                );
            } catch (\Illuminate\Validation\ValidationException $exception) {
                echo 'INSUFFICIENT_STOCK';
            }
            PHP,
            $productId,
            $invoiceId,
            $userId
        );

        $process = new Process(
            [
                PHP_BINARY,

                base_path('artisan'),

                'tinker',

                '--execute='.$code,
            ],
            base_path()
        );

        $process->setTimeout(
            30
        );

        return $process;
    }
}
