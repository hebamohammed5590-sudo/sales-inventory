<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MoneyHelperTest extends TestCase
{
    public function test_it_formats_zero_cents(): void
    {
        $this->assertSame(
            '0.00',
            money(0)
        );
    }

    public function test_it_formats_positive_cents_without_floating_point_math(): void
    {
        $this->assertSame(
            '19.99',
            money(1999)
        );

        $this->assertSame(
            '20.00',
            money(2000)
        );
    }

    public function test_it_formats_negative_cents(): void
    {
        $this->assertSame(
            '-12.34',
            money(-1234)
        );
    }

    public function test_it_accepts_numeric_database_strings(): void
    {
        $this->assertSame(
            '70.11',
            money('7011')
        );
    }
}