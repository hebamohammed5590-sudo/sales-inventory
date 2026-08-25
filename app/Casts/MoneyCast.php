<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    public function get(
        Model $model,
        string $key,
        mixed $value,
        array $attributes
    ): ?string {
        if ($value === null) {
            return null;
        }

        $amount = (int) $value;

        $whole = intdiv($amount, 100);
        $fraction = $amount % 100;

        return sprintf('%d.%02d', $whole, $fraction);
    }

    public function set(
        Model $model,
        string $key,
        mixed $value,
        array $attributes
    ): ?int {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new InvalidArgumentException(
                "Invalid money value for {$key}."
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            '0'
        );

        $fraction = str_pad($fraction, 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }
}
