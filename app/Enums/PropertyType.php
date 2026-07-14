<?php

namespace App\Enums;

enum PropertyType: string
{
    case Apartment = 'apartment';
    case House = 'house';
    case Commercial = 'commercial';
    case Studio = 'studio';

    public function label(): string
    {
        return match ($this) {
            self::Apartment => 'Apartamento',
            self::House => 'Casa',
            self::Commercial => 'Comercial',
            self::Studio => 'Studio',
        };
    }
}
