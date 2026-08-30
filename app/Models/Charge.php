<?php

namespace App\Models;

use App\Enums\ChargeStatus;
use App\Services\Finance;
use App\Services\RateioService;
use Database\Factories\ChargeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `original_amount` is the *full* amount due for this reference month,
 * already including any Rateio share; `rateio_amount` is not an addition
 * on top of it but a breakdown of how much of `original_amount` is
 * Rateio versus rent (`original_amount - rateio_amount` = rent alone) —
 * see {@see RateioService} and CONTEXT.md's Rateio entry.
 * Interest/fines for a late payment are computed on demand
 * ({@see Finance::computeAmountDue()}), never written back
 * to `original_amount`.
 */
#[Fillable([
    'contract_id',
    'receiver_id',
    'reference',
    'due_date',
    'original_amount',
    'status',
    'mercado_pago_order_id',
    'mercado_pago_transaction_id',
    'payment_url',
    'pix_qr_code',
    'pix_qr_code_base64',
    'pix_expires_at',
    'rateio_amount',
    'last_reminder_event',
    'last_reminder_sent_at',
])]
class Charge extends Model
{
    /** @use HasFactory<ChargeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'original_amount' => 'decimal:2',
            'status' => ChargeStatus::class,
            'pix_expires_at' => 'datetime',
            'rateio_amount' => 'decimal:2',
            'last_reminder_sent_at' => 'datetime',
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

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
