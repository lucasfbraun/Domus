<?php

namespace App\Services;

use App\Enums\SignatureStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\ContractInspectionPhoto;
use App\Models\ContractTemplate;
use App\Support\BrazilianDocument;
use App\Support\BrazilianPhone;
use App\Support\ContractTemplateVariables;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Generates a Contract's PDF document from its ContractTemplate (see
 * docs/adr/0003-restricted-token-substitution-for-contract-templates.md
 * for the closed-catalog token substitution this relies on), and manages
 * the three separate signed-document upload paths that follow: the
 * tenant's own signed upload ({@see storeSignedUpload()}), the owner's
 * physically-collected signed copy the admin digitizes ({@see
 * storeOwnerSignedUpload()}), and the admin's approve/reject review of the
 * tenant's upload ({@see approve()}/{@see reject()}). Each keeps its own
 * path/timestamp columns on Contract rather than sharing one, since they
 * represent independent signatures with independent review states.
 */
class ContractDocumentService
{
    public const MAX_SIGNED_FILE_BYTES = 15 * 1024 * 1024;

    public function generate(Contract $contract, ContractTemplate $template): Contract
    {
        $contract->loadMissing(['tenant', 'property.owners', 'receiver', 'inspectionPhotos']);

        $variables = $this->buildVariables($contract);
        $contractText = $this->renderTemplate($template->content, $variables);

        // Se o admin colocou {{fotos_vistoria}} no proprio texto do modelo,
        // as fotos ja foram inseridas ali por renderTemplate() — nao
        // duplica com o banner automatico no topo do PDF (fallback para
        // modelos antigos que nao usam a variavel).
        $hasInlinePhotos = ContractTemplateVariables::isReferenced($template->content, 'fotos_vistoria');

        if ($contract->generated_document_path) {
            Storage::disk('local')->delete($contract->generated_document_path);
        }

        $path = "contracts/{$contract->id}/generated-".now()->timestamp.'.pdf';
        $pdf = Pdf::loadView('pdf.contract', [
            'contractText' => $contractText,
            'photosHtml' => $hasInlinePhotos ? '' : $variables['fotos_vistoria'],
        ]);
        Storage::disk('local')->put($path, $pdf->output());

        $contract->update([
            'template_id' => $template->id,
            'contract_text' => $contractText,
            'signature_status' => SignatureStatus::AwaitingSignature,
            'signed_document_path' => null,
            'signed_file_name' => null,
            'signed_uploaded_at' => null,
            'reviewed_at' => null,
            'review_note' => null,
            'generated_document_path' => $path,
            'generated_document_updated_at' => now(),
        ]);

        return $contract->fresh();
    }

    public function storeSignedUpload(Contract $contract, UploadedFile $file): Contract
    {
        if ($contract->signature_status === SignatureStatus::NotGenerated) {
            throw new \InvalidArgumentException('O contrato ainda nao foi gerado a partir de um modelo.');
        }

        if ($contract->signature_status === SignatureStatus::Approved) {
            throw new \InvalidArgumentException('Este contrato ja foi aprovado.');
        }

        if ($file->getSize() > self::MAX_SIGNED_FILE_BYTES) {
            throw new \InvalidArgumentException('Arquivo muito grande (limite de 15MB).');
        }

        if ($contract->signed_document_path) {
            Storage::disk('local')->delete($contract->signed_document_path);
        }

        $path = $file->store("contracts/{$contract->id}/signed", 'local');

        $contract->update([
            'signed_document_path' => $path,
            'signed_file_name' => $file->getClientOriginalName(),
            'signed_uploaded_at' => now(),
            'signature_status' => SignatureStatus::InReview,
            'reviewed_at' => null,
            'review_note' => null,
        ]);

        return $contract->fresh();
    }

    /**
     * Documento assinado fisicamente pelo proprietario, colhido e reenviado
     * pelo admin. So depois desse upload e possivel marcar owner_signed_at.
     */
    public function storeOwnerSignedUpload(Contract $contract, UploadedFile $file): Contract
    {
        if ($file->getSize() > self::MAX_SIGNED_FILE_BYTES) {
            throw new \InvalidArgumentException('Arquivo muito grande (limite de 15MB).');
        }

        if ($contract->owner_signed_document_path) {
            Storage::disk('local')->delete($contract->owner_signed_document_path);
        }

        $path = $file->store("contracts/{$contract->id}/owner-signed", 'local');

        $contract->update([
            'owner_signed_document_path' => $path,
        ]);

        return $contract->fresh();
    }

    public function approve(Contract $contract, ?string $note = null): Contract
    {
        $contract->update([
            'signature_status' => SignatureStatus::Approved,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);

        return $contract->fresh();
    }

    public function reject(Contract $contract, ?string $note = null): Contract
    {
        $contract->update([
            'signature_status' => SignatureStatus::Rejected,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);

        return $contract->fresh();
    }

    public function generateReceiptPdf(Contract $contract, Charge $charge): string
    {
        $charge->loadMissing(['contract.tenant', 'contract.property', 'contract.receiver', 'payments']);

        $pdf = Pdf::loadView('pdf.receipt', [
            'charge' => $charge,
            'contract' => $contract,
            'payment' => $charge->payments()->latest('paid_at')->first(),
        ]);

        $path = "receipts/charge-{$charge->id}-".now()->timestamp.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * @return array<string, string>
     */
    private function buildVariables(Contract $contract): array
    {
        return [
            'inquilino_nome' => $contract->tenant->name,
            'inquilino_documento' => BrazilianDocument::format($contract->tenant->document),
            'inquilino_email' => $contract->tenant->email,
            'inquilino_whatsapp' => BrazilianPhone::format($contract->tenant->whatsapp),
            'imovel_nome' => $contract->property->name,
            'imovel_endereco' => $contract->property->address,
            'imovel_tipo' => $contract->property->type->label(),
            'recebedor_nome' => $contract->receiver->name,
            'recebedor_documento' => BrazilianDocument::format($contract->receiver->document),
            'proprietario_nome' => $contract->property->owners->pluck('name')->implode(', '),
            'proprietario_documento' => $contract->property->owners->pluck('document')->map(fn ($document) => BrazilianDocument::format($document))->implode(', '),
            'proprietario_email' => $contract->property->owners->pluck('email')->filter()->implode(', '),
            'proprietario_telefone' => $contract->property->owners->pluck('phone')->filter()->map(fn ($phone) => BrazilianPhone::format($phone))->implode(', '),
            'valor_aluguel' => Money::format((float) $contract->monthly_rent),
            'dia_vencimento' => (string) $contract->due_day,
            'data_inicio' => $contract->starts_at->format('d/m/Y'),
            'data_fim' => $contract->ends_at->format('d/m/Y'),
            'multa_percentual' => number_format((float) $contract->fine_rate * 100, 0),
            'juros_percentual' => number_format((float) $contract->monthly_interest_rate * 100, 0),
            'carencia_dias' => (string) $contract->grace_days,
            'data_geracao' => now()->timezone('America/Sao_Paulo')->format('d/m/Y'),
            'fotos_vistoria' => $this->buildInspectionPhotosHtml($contract->inspectionPhotos),
        ];
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function renderTemplate(string $content, array $variables): string
    {
        $isHtml = preg_match('/<(p|h[1-3]|ul|ol|div|br|span)\b/i', $content) === 1;

        $rendered = preg_replace_callback(
            '/{{\s*(\w+)\s*}}/u',
            function (array $matches) use ($variables, $isHtml): string {
                if (! array_key_exists($matches[1], $variables)) {
                    return $matches[0];
                }

                if ($isHtml && ContractTemplateVariables::isHtmlKey($matches[1])) {
                    return $variables[$matches[1]];
                }

                return $isHtml
                    ? e($variables[$matches[1]])
                    : $variables[$matches[1]];
            },
            $content,
        ) ?? $content;

        if (! $isHtml) {
            return nl2br(e($rendered));
        }

        return strip_tags(
            $rendered,
            '<p><br><strong><b><em><i><u><h1><h2><h3><ul><ol><li><span><img>',
        );
    }

    /**
     * Galeria HTML das fotos de vistoria do contrato: usada tanto como
     * valor da variavel {{fotos_vistoria}} quanto no banner automatico de
     * fallback (topo do PDF) para modelos que nao usam a variavel — uma
     * unica fonte de verdade para o markup, ver docs/adr/0002 sobre a
     * excecao de storage bruto para essas fotos.
     *
     * Usa apenas <span>/<img> (com display:block via CSS), nunca <div>: o
     * token {{fotos_vistoria}} e salvo dentro de um <span
     * data-template-variable> pelo editor, e um <div> ali dentro seria HTML
     * bloco aninhado em inline — o strip_tags de renderTemplate() so
     * permite tags inline por isso.
     *
     * @param  Collection<int, ContractInspectionPhoto>  $photos
     */
    private function buildInspectionPhotosHtml(Collection $photos): string
    {
        $html = '';

        foreach ($photos as $photo) {
            if (! $photo->storage_path || ! Storage::disk('local')->exists($photo->storage_path)) {
                continue;
            }

            $caption = trim(collect([$photo->room, $photo->caption])->filter()->implode(' — '));
            $src = Storage::disk('local')->path($photo->storage_path);

            $html .= '<span class="photo"><img src="'.e($src).'" alt="'.e($caption).'">';

            if ($caption !== '') {
                $html .= '<span class="caption">'.e($caption).'</span>';
            }

            $html .= '</span>';
        }

        return $html;
    }
}
