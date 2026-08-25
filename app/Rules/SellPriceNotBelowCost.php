<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class SellPriceNotBelowCost implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        $costPrice = $this->data['cost_price'] ?? null;

        if ($costPrice === null || $value === null) {
            return;
        }

        $costPrice = trim((string) $costPrice);

        $sellPrice = trim((string) $value);

        if (! $this->isValidAmount($costPrice)) {
            return;
        }

        if (! $this->isValidAmount($sellPrice)) {
            return;
        }

        if (
            $this->toCents($sellPrice)
            < $this->toCents($costPrice)
        ) {
            $fail(
                __('The selling price must be greater than or equal to the cost price.')
            );
        }
    }

    private function isValidAmount(string $value): bool
    {
        return preg_match(
            '/^\d+(?:\.\d{1,2})?$/',
            $value
        ) === 1;
    }

    private function toCents(string $value): int
    {
        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');
    }
}
