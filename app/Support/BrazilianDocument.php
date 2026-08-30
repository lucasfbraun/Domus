<?php

namespace App\Support;

/** Helpers for CPF/CNPJ document strings as entered by users. */
class BrazilianDocument
{
    /** Strips everything but digits, e.g. `"123.456.789-00"` -> `"12345678900"`. */
    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    /**
     * Formats a stored (digits-only) document for display: CPF
     * (`123.456.789-00`) for 11 digits or fewer, CNPJ
     * (`11.222.333/0001-81`) otherwise. Mirrors
     * resources/js/lib/brazilian-masks.ts's `formatCpfCnpj` so the contract
     * document shows the same punctuation the admin forms already do.
     */
    public static function format(?string $value): string
    {
        $digits = substr(self::digits($value), 0, 14);

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) <= 11) {
            $formatted = preg_replace('/(\d{3})(\d)/', '$1.$2', $digits, 1);
            $formatted = preg_replace('/(\d{3})(\d)/', '$1.$2', $formatted, 1);

            return preg_replace('/(\d{3})(\d{1,2})$/', '$1-$2', $formatted, 1);
        }

        $formatted = preg_replace('/^(\d{2})(\d)/', '$1.$2', $digits, 1);
        $formatted = preg_replace('/^(\d{2})\.(\d{3})(\d)/', '$1.$2.$3', $formatted, 1);
        $formatted = preg_replace('/\.(\d{3})(\d)/', '.$1/$2', $formatted, 1);

        return preg_replace('/(\d{4})(\d)/', '$1-$2', $formatted, 1);
    }
}
