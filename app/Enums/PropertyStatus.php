<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Available = 'available';
    case Rented = 'rented';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';
}
