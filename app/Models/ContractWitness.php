<?php

namespace App\Models;

use Database\Factories\ContractWitnessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['contract_id', 'receiver_id', 'signed_at'])]
class ContractWitness extends Model
{
    /** @use HasFactory<ContractWitnessFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'signed_at' => 'datetime',
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
     * @return BelongsTo<Receiver, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Receiver::class);
    }
}
