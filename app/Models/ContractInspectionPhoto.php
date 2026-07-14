<?php

namespace App\Models;

use Database\Factories\ContractInspectionPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'contract_id',
    'storage_path',
    'file_name',
    'content_type',
    'caption',
    'room',
    'position',
])]
class ContractInspectionPhoto extends Model
{
    /** @use HasFactory<ContractInspectionPhotoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
