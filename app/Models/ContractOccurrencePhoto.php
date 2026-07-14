<?php

namespace App\Models;

use Database\Factories\ContractOccurrencePhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'occurrence_id',
    'storage_path',
    'file_name',
    'content_type',
])]
class ContractOccurrencePhoto extends Model
{
    /** @use HasFactory<ContractOccurrencePhotoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ContractOccurrence, $this>
     */
    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(ContractOccurrence::class, 'occurrence_id');
    }
}
