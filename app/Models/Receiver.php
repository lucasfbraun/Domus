<?php

namespace App\Models;

use Database\Factories\ReceiverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'user_id',
    'name',
    'document',
    'email',
    'mercado_pago_account',
    'active',
    'mp_user_id',
    'mp_access_token',
    'mp_refresh_token',
    'mp_token_expires_at',
    'mp_connected_at',
    'mp_live_mode',
])]
#[Hidden(['mp_access_token', 'mp_refresh_token'])]
class Receiver extends Model
{
    /** @use HasFactory<ReceiverFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'mp_live_mode' => 'boolean',
            'mp_access_token' => 'encrypted',
            'mp_refresh_token' => 'encrypted',
            'mp_token_expires_at' => 'datetime',
            'mp_connected_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
