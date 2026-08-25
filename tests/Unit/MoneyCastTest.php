<?php

namespace Tests\Unit;

use App\Casts\MoneyCast;
use App\Models\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyCastTest extends TestCase
{
    private MoneyCast $cast;

    private User $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cast = new MoneyCast;
        $this->model = new User;
    }

    public function test_it_stores_money_as_integer_cents(): void
    {
        $result = $this->cast->set(
            $this->model,
            'price',
            '150.75',
            []
        );

        $this->assertSame(15075, $result);
    }

    public function test_it_reads_integer_cents_as_decimal_string(): void
    {
        $result = $this->cast->get(
            $this->model,
            'price',
            15075,
            []
        );

        $this->assertSame('150.75', $result);
    }

    public function test_it_handles_whole_amounts(): void
    {
        $result = $this->cast->set(
            $this->model,
            'price',
            '20',
            []
        );

        $this->assertSame(2000, $result);
    }

    public function test_it_handles_one_decimal_place(): void
    {
        $result = $this->cast->set(
            $this->model,
            'price',
            '19.9',
            []
        );

        $this->assertSame(1990, $result);
    }

    public function test_it_handles_zero(): void
    {
        $stored = $this->cast->set(
            $this->model,
            'price',
            '0',
            []
        );

        $displayed = $this->cast->get(
            $this->model,
            'price',
            0,
            []
        );

        $this->assertSame(0, $stored);
        $this->assertSame('0.00', $displayed);
    }

    public function test_money_addition_has_no_precision_errors(): void
    {
        $firstAmount = $this->cast->set(
            $this->model,
            'price',
            '19.99',
            []
        );

        $secondAmount = $this->cast->set(
            $this->model,
            'price',
            '0.01',
            []
        );

        $totalInCents = $firstAmount + $secondAmount;

        $formattedTotal = $this->cast->get(
            $this->model,
            'price',
            $totalInCents,
            []
        );

        $this->assertSame(2000, $totalInCents);
        $this->assertSame('20.00', $formattedTotal);
    }

    public function test_it_rejects_more_than_two_decimal_places(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cast->set(
            $this->model,
            'price',
            '19.999',
            []
        );
    }

    public function test_it_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cast->set(
            $this->model,
            'price',
            '-10.00',
            []
        );
    }

    public function test_it_rejects_non_numeric_values(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cast->set(
            $this->model,
            'price',
            'invalid',
            []
        );
    }

    public function test_it_handles_null_values(): void
    {
        $stored = $this->cast->set(
            $this->model,
            'price',
            null,
            []
        );

        $displayed = $this->cast->get(
            $this->model,
            'price',
            null,
            []
        );

        $this->assertNull($stored);
        $this->assertNull($displayed);
    }
}
