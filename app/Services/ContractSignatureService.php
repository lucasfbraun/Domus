<?php

namespace App\Services;

use App\Models\Contract;

class ContractSignatureService
{
    public function isContractReadyForTenantSignature(Contract $contract): bool
    {
        $contract->loadMissing(['property.owner', 'witnesses']);

        $ownerGateOk = ! $contract->property?->owner_id || $contract->owner_signed_at !== null;
        $witnessesGateOk = $contract->witnesses->every(fn ($witness) => $witness->signed_at !== null);

        return $ownerGateOk && $witnessesGateOk;
    }
}
