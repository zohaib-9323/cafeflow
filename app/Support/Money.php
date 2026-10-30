<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Money is handled in integer cents so totals never pick up float rounding drift.
 * The database stores DECIMAL(10,2); this converts at the edges.
 */
final class Money
{
    public static function toCents(string|int|float|null $amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }
        $amount = (string) $amount;
        if (! preg_match('/^(-)?(\d+)(?:\.(\d{1,}))?$/', $amount, $m)) {
            throw new InvalidArgumentException("Invalid money amount [{$amount}]");
        }
        // Round half up on the third decimal, e.g. "1.005" -> 101.
        $fraction = str_pad($m[3] ?? '', 3, '0');
        $cents = (int) $m[2] * 100 + (int) substr($fraction, 0, 2) + ((int) $fraction[2] >= 5 ? 1 : 0);

        return $m[1] === '-' ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return sprintf('%s%d.%02d', $sign, intdiv($cents, 100), $cents % 100);
    }

    /** Percentage of an amount, rounded to the nearest cent (half up). */
    public static function percentOf(int $cents, string|float $percent): int
    {
        return (int) round($cents * (float) $percent / 100, 0, PHP_ROUND_HALF_UP);
    }

    public static function format(string|int|float|null $amount): string
    {
        $cents = is_int($amount) ? $amount : self::toCents($amount);

        return ($cents < 0 ? '-' : '').config('cafeflow.currency_symbol', '$').number_format(abs($cents) / 100, 2);
    }
}
