<?php

namespace App\Support;

class Fmt
{
    public static function money(float|int|string|null $amount, string $currency = 'Rp'): string
    {
        return trim($currency.' '.number_format((float) $amount, 0, ',', '.'));
    }
}
