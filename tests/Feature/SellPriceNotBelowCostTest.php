<?php

namespace Tests\Feature;

use App\Rules\SellPriceNotBelowCost;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SellPriceNotBelowCostTest extends TestCase
{
    public function test_sell_price_above_cost_price_is_valid(): void
    {
        $validator = Validator::make(
            [
                'cost_price' => '100.00',
                'sell_price' => '150.00',
            ],
            [
                'sell_price' => [
                    new SellPriceNotBelowCost,
                ],
            ]
        );

        $this->assertTrue(
            $validator->passes()
        );
    }

    public function test_sell_price_equal_to_cost_price_is_valid(): void
    {
        $validator = Validator::make(
            [
                'cost_price' => '100.00',
                'sell_price' => '100.00',
            ],
            [
                'sell_price' => [
                    new SellPriceNotBelowCost,
                ],
            ]
        );

        $this->assertTrue(
            $validator->passes()
        );
    }

    public function test_sell_price_below_cost_price_is_invalid(): void
    {
        $validator = Validator::make(
            [
                'cost_price' => '100.00',
                'sell_price' => '99.99',
            ],
            [
                'sell_price' => [
                    new SellPriceNotBelowCost,
                ],
            ]
        );

        $this->assertTrue(
            $validator->fails()
        );

        $this->assertArrayHasKey(
            'sell_price',
            $validator->errors()->toArray()
        );
    }

    public function test_rule_compares_money_without_float_precision_errors(): void
    {
        $validator = Validator::make(
            [
                'cost_price' => '19.99',
                'sell_price' => '20.00',
            ],
            [
                'sell_price' => [
                    new SellPriceNotBelowCost,
                ],
            ]
        );

        $this->assertTrue(
            $validator->passes()
        );
    }

    public function test_rule_accepts_amounts_with_one_decimal_place(): void
    {
        $validator = Validator::make(
            [
                'cost_price' => '19.9',
                'sell_price' => '19.90',
            ],
            [
                'sell_price' => [
                    new SellPriceNotBelowCost,
                ],
            ]
        );

        $this->assertTrue(
            $validator->passes()
        );
    }

    public function test_rule_returns_clear_message_when_price_is_too_low(): void
    {
        $validator = Validator::make(
            [
                'cost_price' => '100.00',
                'sell_price' => '90.00',
            ],
            [
                'sell_price' => [
                    new SellPriceNotBelowCost,
                ],
            ]
        );

        $this->assertSame(
            'The selling price must be greater than or equal to the cost price.',
            $validator->errors()->first('sell_price')
        );
    }
}
