<?php

namespace App\Services;

use App\Models\Contract;

class ContractSignatureService
{
    public function isContractReadyForTenantSignature(Contract $contract): bool
    {
        $contract->loadMissing(['property.owners', 'witnesses']);

        $hasOwners = $contract->property?->owners?->isNotEmpty() ?? false;
        $ownerGateOk = ! $hasOwners || $contract->owner_signed_at !== null;
        $witnessesGateOk = $contract->witnesses->every(fn ($witness) => $witness->signed_at !== null);

        return $ownerGateOk && $witnessesGateOk;
    }
}
