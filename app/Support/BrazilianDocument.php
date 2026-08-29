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
}
