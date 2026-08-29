<?php

namespace App\Enums;

/**
 * Occupancy/listing state of a Property. Set by the admin — nothing in the
 * app currently flips it automatically when a Contract starts or ends.
 */
enum PropertyStatus: string
{
    case Available = 'available';
    case Rented = 'rented';
    case Maintenance = 'maintenance';
    case Inactive = 'inactive';
}
