<?php

namespace App\Support;

class BrazilianDocument
{
    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }
}
