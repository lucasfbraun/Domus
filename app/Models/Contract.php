<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\SignatureStatus;
use App\Services\ContractDocumentService;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Three independent signed-document paths, each with its own
 * timestamp/status, deliberately not sharing columns:
 * `generated_document_path` (the system-generated PDF from the
 * ContractTemplate), `signed_document_path`/`signed_uploaded_at`/
 * `reviewed_at`/`review_note` (the tenant's own signed upload, reviewed
 * by an admin — see {@see ContractDocumentService}), and
 * `owner_signed_at`/`owner_signed_document_path` (the owner's physically
 * collected signature, no review step). `ContractStatus` (the lease
 * term) and `SignatureStatus` (this document's signing workflow) are
 * independent — a Contract can be Active with no signed document at all.
 */
#[Fillable([
    'property_id',
    'tenant_id',
    'receiver_id',
    'monthly_rent',
    'due_day',
    'starts_at',
    'ends_at',
    'fine_rate',
    'monthly_interest_rate',
    'grace_days',
    'status',
    'template_id',
    'contract_text',
    'signature_status',
    'signed_document_path',
    'signed_file_name',
    'signed_uploaded_at',
    'reviewed_at',
    'review_note',
    'generated_document_path',
    'generated_document_updated_at',
    'owner_signed_at',
    'owner_signed_document_path',
    'expiring_reminder_sent_at',
])]
class Contract extends Model
{
    /** @use HasFactory<ContractFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_rent' => 'decimal:2',
            'fine_rate' => 'decimal:4',
            'monthly_interest_rate' => 'decimal:4',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => ContractStatus::class,
            'signature_status' => SignatureStatus::class,
            'signed_uploaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'generated_document_updated_at' => 'datetime',
            'owner_signed_at' => 'datetime',
            'expiring_reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Receiver, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Receiver::class);
    }

    /**
     * @return BelongsTo<ContractTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    /**
     * @return HasMany<Charge, $this>
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * @return HasMany<Deposit, $this>
     */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    /**
     * @return HasMany<ContractWitness, $this>
     */
    public function witnesses(): HasMany
    {
        return $this->hasMany(ContractWitness::class);
    }

    /**
     * @return HasMany<ContractInspectionPhoto, $this>
     */
    public function inspectionPhotos(): HasMany
    {
        return $this->hasMany(ContractInspectionPhoto::class);
    }

    /**
     * @return HasMany<ContractOccurrence, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(ContractOccurrence::class);
    }
}
