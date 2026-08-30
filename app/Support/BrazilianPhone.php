<?php

namespace App\Support;

class BrazilianPhone
{
    public const DDI = '55';

    /**
     * Normalize a Brazilian phone to digits only with fixed DDI 55.
     *
     * Accepts values with or without mask/DDI. Returns null for empty input.
     */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, self::DDI) && strlen($digits) >= 12) {
            return $digits;
        }

        return self::DDI.$digits;
    }

    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    /**
     * Formats a stored (DDI-prefixed) phone for display, without the DDI:
     * `(11) 98888-0000` for a 9-digit mobile number, `(11) 3333-0000` for
     * an 8-digit landline. Mirrors resources/js/lib/brazilian-masks.ts's
     * `formatPhone` so the contract document shows the same punctuation
     * the admin forms already do.
     */
    public static function format(?string $value): string
    {
        $digits = self::digits($value);

        if (str_starts_with($digits, self::DDI) && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        $digits = substr($digits, 0, 11);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) <= 10) {
            $formatted = preg_replace('/(\d{2})(\d)/', '($1) $2', $digits, 1);

            return preg_replace('/(\d{4})(\d)/', '$1-$2', $formatted, 1);
        }

        $formatted = preg_replace('/(\d{2})(\d)/', '($1) $2', $digits, 1);

        return preg_replace('/(\d{5})(\d)/', '$1-$2', $formatted, 1);
    }
}
