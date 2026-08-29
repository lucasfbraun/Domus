<?php

namespace App\Services;

use App\Models\Contract;

class ContractSignatureService
{
    /**
     * Gates whether the tenant is allowed to sign yet: every attached
     * ContractWitness must already be signed, and — only if the property
     * has at least one Owner on record — the owner side must be signed
     * too (`owner_signed_at`). A property with no Owner on file skips that
     * gate entirely rather than blocking the tenant forever.
     */
    public function isContractReadyForTenantSignature(Contract $contract): bool
    {
        $contract->loadMissing(['property.owners', 'witnesses']);

        $hasOwners = $contract->property?->owners?->isNotEmpty() ?? false;
        $ownerGateOk = ! $hasOwners || $contract->owner_signed_at !== null;
        $witnessesGateOk = $contract->witnesses->every(fn ($witness) => $witness->signed_at !== null);

        return $ownerGateOk && $witnessesGateOk;
    }
}
