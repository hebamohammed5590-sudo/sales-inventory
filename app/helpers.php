<?php

if (! function_exists('money')) {
    function money(
        int|string $amountInCents
    ): string {
        $amount = (int) $amountInCents;

        $negative = $amount < 0;

        $amount = abs($amount);

        return sprintf(
            '%s%d.%02d',
            $negative ? '-' : '',
            intdiv($amount, 100),
            $amount % 100
        );
    }
}
