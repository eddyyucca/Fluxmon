<?php

namespace App\Support;

class Fmt
{
    /** Thousands and decimal separators follow the interface language: 1.000 (id) or 1,000 (en). */
    public static function money(float|int|string|null $amount, string $currency = 'Rp'): string
    {
        [$decimal, $thousands] = app()->getLocale() === 'id' ? [',', '.'] : ['.', ','];

        return trim($currency.' '.number_format((float) $amount, 0, $decimal, $thousands));
    }
}
