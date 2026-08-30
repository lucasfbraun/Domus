<?php

namespace App\Models;

use App\Enums\PreRegistrationStatus;
use Database\Factories\TenantPreRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'token',
    'status',
    'name',
    'document',
    'email',
    'whatsapp',
    'resident_count',
    'invited_at',
    'expires_at',
    'submitted_at',
    'reviewed_at',
    'reviewed_by',
    'rejection_note',
    'tenant_id',
])]
class TenantPreRegistration extends Model
{
    /** @use HasFactory<TenantPreRegistrationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PreRegistrationStatus::class,
            'resident_count' => 'integer',
            'invited_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
