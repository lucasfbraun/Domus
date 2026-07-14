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
}
