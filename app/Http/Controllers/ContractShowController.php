<?php

namespace App\Http\Controllers;

use App\Enums\SignatureStatus;
use App\Enums\UserRole;
use App\Models\Contract;
use App\Models\User;
use App\Services\ContractSignatureService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractShowController extends Controller
{
    public function show(Request $request, Contract $contract, ContractSignatureService $signatureService): Response
    {
        $this->authorize('view', $contract);

        $user = $request->user();

        $readyForTenantSignature = $signatureService->isContractReadyForTenantSignature($contract);
        $canUploadSigned = $readyForTenantSignature && in_array($contract->signature_status, [
            SignatureStatus::AwaitingSignature,
            SignatureStatus::Rejected,
        ], true);

        return Inertia::render('contracts/Show', [
            'contract' => $contract->load([
                'property.owner',
                'tenant',
                'receiver',
                'witnesses.receiver',
                'charges',
                'inspectionPhotos',
                'occurrences.photos',
            ]),
            'readyForTenantSignature' => $readyForTenantSignature,
            'canUploadSigned' => $canUploadSigned,
            'isTenant' => $user instanceof User && $user->hasRole(UserRole::Tenant),
            'isAdmin' => $user instanceof User && $user->hasRole(UserRole::Admin),
        ]);
    }
}
