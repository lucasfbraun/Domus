<?php

namespace App\Enums;

/**
 * How a Rateio's total amount is divided across the participating
 * properties/contracts (see RateioService::buildWeights()).
 */
enum RateioSplitMode: string
{
    /** Weighted by each contract's tenant resident_count. */
    case Residents = 'residents';

    /** Same amount for every participating property. */
    case Equal = 'equal';
}
