<?php

namespace App\Models;

use App\Enums\OccurrenceStatus;
use Database\Factories\ContractOccurrenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'contract_id',
    'tenant_id',
    'description',
    'status',
    'resolved_at',
    'resolution_note',
])]
class ContractOccurrence extends Model
{
    /** @use HasFactory<ContractOccurrenceFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OccurrenceStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Contract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<ContractOccurrencePhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ContractOccurrencePhoto::class, 'occurrence_id');
    }
}
