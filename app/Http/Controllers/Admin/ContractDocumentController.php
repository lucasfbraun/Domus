<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SignatureStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewContractDocumentRequest;
use App\Mail\ContractDocumentReviewedMail;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\User;
use App\Services\ContractDocumentService;
use App\Services\ContractSignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractDocumentController extends Controller
{
    public function generate(Request $request, Contract $contract, ContractDocumentService $documentService): RedirectResponse
    {
        $this->authorize('update', $contract);

        $request->validate(['template_id' => ['required', 'integer', 'exists:contract_templates,id']]);

        $template = ContractTemplate::query()->findOrFail($request->integer('template_id'));
        $documentService->generate($contract, $template);

        return back();
    }

    /**
     * The readiness/status gate below only applies when the acting user
     * is the Tenant (self-service upload from their own portal) — an
     * admin uploading on the tenant's behalf (e.g. digitizing a
     * physically-signed copy) is intentionally not gated the same way
     * {@see uploadOwnerSigned()} isn't, since the admin is already
     * trusted to manage the whole signature workflow through other
     * endpoints regardless. Don't "fix" this asymmetry without checking
     * that intent first.
     */
    public function uploadSigned(
        Request $request,
        Contract $contract,
        ContractDocumentService $documentService,
        ContractSignatureService $signatureService,
    ): RedirectResponse {
        $this->authorize('view', $contract);

        $user = $request->user();

        if ($user instanceof User && $user->hasRole(UserRole::Tenant)) {
            abort_unless(
                $signatureService->isContractReadyForTenantSignature($contract)
                && in_array($contract->signature_status, [
                    SignatureStatus::AwaitingSignature,
                    SignatureStatus::Rejected,
                ], true),
                403,
                'Contrato ainda nao esta pronto para assinatura do inquilino.',
            );
        }

        $request->validate(['signed_document' => ['required', 'file', 'mimes:pdf', 'max:15360']]);

        $documentService->storeSignedUpload($contract, $request->file('signed_document'));

        return back();
    }

    /**
     * Documento assinado fisicamente pelo proprietario, colhido e reenviado
     * pelo admin. Pre-requisito pra ContractController::markOwnerSigned.
     */
    public function uploadOwnerSigned(
        Request $request,
        Contract $contract,
        ContractDocumentService $documentService,
    ): RedirectResponse {
        $this->authorize('update', $contract);

        $request->validate([
            'owner_signed_document' => ['required', 'file', 'mimes:pdf', 'max:15360'],
        ]);

        $documentService->storeOwnerSignedUpload($contract, $request->file('owner_signed_document'));

        return back();
    }

    public function review(
        ReviewContractDocumentRequest $request,
        Contract $contract,
        ContractDocumentService $documentService,
    ): RedirectResponse {
        $this->authorize('update', $contract);

        if ($request->input('action') === 'approve') {
            $documentService->approve($contract, $request->input('review_note'));
        } else {
            $documentService->reject($contract, $request->input('review_note'));
        }

        $contract->loadMissing(['tenant', 'receiver', 'property']);
        $approved = $request->input('action') === 'approve';
        $recipients = array_values(array_filter([
            $contract->tenant?->email,
            $contract->receiver?->email,
        ]));

        if ($recipients !== []) {
            Mail::to($recipients)->send(new ContractDocumentReviewedMail(
                propertyName: $contract->property->name ?? 'imovel',
                approved: $approved,
                reviewNote: $request->input('review_note'),
            ));
        }

        return back();
    }

    public function downloadGenerated(Contract $contract): BinaryFileResponse
    {
        $this->authorize('view', $contract);
        abort_unless($contract->generated_document_path !== null, 404);

        return response()->download(
            Storage::disk('local')->path($contract->generated_document_path),
            'contrato-'.$contract->id.'.pdf',
        );
    }

    public function downloadSigned(Contract $contract): BinaryFileResponse
    {
        $this->authorize('view', $contract);
        abort_unless($contract->signed_document_path !== null, 404);

        return response()->download(
            Storage::disk('local')->path($contract->signed_document_path),
            $contract->signed_file_name ?? 'contrato-assinado.pdf',
        );
    }

    public function downloadOwnerSigned(Contract $contract): BinaryFileResponse
    {
        $this->authorize('view', $contract);
        abort_unless($contract->owner_signed_document_path !== null, 404);

        return response()->download(
            Storage::disk('local')->path($contract->owner_signed_document_path),
            'contrato-proprietario-assinado-'.$contract->id.'.pdf',
        );
    }
}
