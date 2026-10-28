<?php

namespace Bastihilger\MiniStatamicShop;

class Money
{
    /**
     * Parse a price as editors type it ("36", "7,50", "1.234,50", "1,234.50") into cents.
     */
    public static function parse(mixed $value): int
    {
        if (is_int($value) || is_float($value)) {
            return (int) round($value * 100);
        }

        $str = trim((string) $value);

        if (str_contains($str, '.') && str_contains($str, ',')) {
            $str = strrpos($str, '.') > strrpos($str, ',')
                ? str_replace(',', '', $str)
                : str_replace(',', '.', str_replace('.', '', $str));
        } elseif (str_contains($str, ',')) {
            $str = str_replace(',', '.', $str);
        }

        return (int) round(((float) $str) * 100);
    }

    /**
     * "3600" → "36,00".
     */
    public static function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }

    /**
     * "3600" → "36,00 €".
     */
    public static function format(int $cents): string
    {
        return static::decimal($cents).' '.config('mini-shop.currency_symbol');
    }

    /**
     * Orders store amounts as euros (floats), like the original KraenkShop did.
     */
    public static function toFloat(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
