<?php

namespace Tests\Feature;

use App\Enums\InvoiceType;
use App\Models\InvoiceSequence;
use App\Models\Setting;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceNumberService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(
            InvoiceNumberService::class
        );
    }

    public function test_sale_invoice_number_has_correct_format(): void
    {
        $number = $this->service->generate(
            InvoiceType::Sale,
            2026
        );

        $this->assertSame(
            'INV-2026-000001',
            $number
        );
    }

    public function test_purchase_invoice_number_has_correct_format(): void
    {
        $number = $this->service->generate(
            InvoiceType::Purchase,
            2026
        );

        $this->assertSame(
            'PUR-2026-000001',
            $number
        );
    }

    public function test_invoice_numbers_increment_sequentially(): void
    {
        $first = $this->service->generate(
            InvoiceType::Sale,
            2026
        );

        $second = $this->service->generate(
            InvoiceType::Sale,
            2026
        );

        $third = $this->service->generate(
            InvoiceType::Sale,
            2026
        );

        $this->assertSame(
            'INV-2026-000001',
            $first
        );

        $this->assertSame(
            'INV-2026-000002',
            $second
        );

        $this->assertSame(
            'INV-2026-000003',
            $third
        );
    }

    public function test_sale_and_purchase_have_separate_sequences(): void
    {
        $saleNumber = $this->service->generate(
            InvoiceType::Sale,
            2026
        );

        $purchaseNumber = $this->service->generate(
            InvoiceType::Purchase,
            2026
        );

        $this->assertSame(
            'INV-2026-000001',
            $saleNumber
        );

        $this->assertSame(
            'PUR-2026-000001',
            $purchaseNumber
        );

        $this->assertDatabaseCount(
            'invoice_sequences',
            2
        );
    }

    public function test_sequence_resets_for_new_year(): void
    {
        $number2026 = $this->service->generate(
            InvoiceType::Sale,
            2026
        );

        $number2027 = $this->service->generate(
            InvoiceType::Sale,
            2027
        );

        $this->assertSame(
            'INV-2026-000001',
            $number2026
        );

        $this->assertSame(
            'INV-2027-000001',
            $number2027
        );

        $this->assertDatabaseCount(
            'invoice_sequences',
            2
        );
    }

    public function test_generating_fifty_invoice_numbers_produces_unique_values(): void
    {
        $numbers = [];

        for ($index = 0; $index < 50; $index++) {
            $numbers[] = $this->service->generate(
                InvoiceType::Sale,
                2026
            );
        }

        $this->assertCount(
            50,
            $numbers
        );

        $this->assertCount(
            50,
            array_unique($numbers)
        );

        $this->assertSame(
            'INV-2026-000050',
            $numbers[49]
        );

        $this->assertDatabaseHas('invoice_sequences', [
            'type' => InvoiceType::Sale->value,
            'year' => 2026,
            'last_number' => 50,
        ]);
    }

    public function test_sale_invoice_uses_prefix_from_settings(): void
    {
        Setting::set(
            'invoice_prefix',
            'SALE'
        );

        $number = $this->service->generate(
            InvoiceType::Sale,
            2026
        );

        $this->assertSame(
            'SALE-2026-000001',
            $number
        );
    }

    public function test_purchase_invoice_uses_prefix_from_settings(): void
    {
        Setting::set(
            'purchase_invoice_prefix',
            'BUY'
        );

        $number = $this->service->generate(
            InvoiceType::Purchase,
            2026
        );

        $this->assertSame(
            'BUY-2026-000001',
            $number
        );
    }

    public function test_sequence_is_saved_in_database(): void
    {
        $this->service->generate(
            InvoiceType::Sale,
            2026
        );

        $sequence = InvoiceSequence::query()
            ->where('type', InvoiceType::Sale->value)
            ->where('year', 2026)
            ->first();

        $this->assertNotNull($sequence);

        $this->assertSame(
            1,
            $sequence->last_number
        );
    }
}
