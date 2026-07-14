<?php

namespace App\Models;

use App\Enums\RateioSplitMode;
use Database\Factories\RateioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category',
    'description',
    'reference',
    'total_amount',
    'invoice_path',
    'invoice_content_type',
    'invoice_file_name',
    'split_mode',
])]
class Rateio extends Model
{
    /** @use HasFactory<RateioFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'split_mode' => RateioSplitMode::class,
        ];
    }

    /**
     * @return HasMany<RateioAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(RateioAllocation::class);
    }
}
