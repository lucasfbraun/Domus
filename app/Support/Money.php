<?php

namespace App\Support;

class Money
{
    public static function currency(): string
    {
        return (string) config('money.currency', 'BRL');
    }

    public static function locale(): string
    {
        return (string) config('money.locale', 'pt-BR');
    }

    public static function decimals(): int
    {
        return (int) config('money.decimals', 2);
    }

    public static function symbol(): string
    {
        return (string) config('money.symbol', 'R$');
    }

    public static function roundCents(float $value): float
    {
        $factor = 10 ** self::decimals();

        return round($value * $factor) / $factor;
    }

    public static function format(float|int|string|null $amount, string $empty = '—'): string
    {
        if ($amount === null || $amount === '') {
            return $empty;
        }

        return self::symbol().' '.number_format(
            (float) $amount,
            self::decimals(),
            ',',
            '.',
        );
    }

    public static function formatForApi(float|int|string|null $amount): string
    {
        return number_format((float) $amount, self::decimals(), '.', '');
    }

    /**
     * @return array{currency: string, locale: string, decimals: int}
     */
    public static function inertiaConfig(): array
    {
        return [
            'currency' => self::currency(),
            'locale' => self::locale(),
            'decimals' => self::decimals(),
        ];
    }
}
