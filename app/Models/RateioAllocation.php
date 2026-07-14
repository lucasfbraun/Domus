<?php

namespace App\Models;

use Database\Factories\RateioAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'rateio_id',
    'property_id',
    'amount',
    'charge_id',
    'applied_at',
])]
class RateioAllocation extends Model
{
    /** @use HasFactory<RateioAllocationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Rateio, $this>
     */
    public function rateio(): BelongsTo
    {
        return $this->belongsTo(Rateio::class);
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<Charge, $this>
     */
    public function charge(): BelongsTo
    {
        return $this->belongsTo(Charge::class);
    }
}
