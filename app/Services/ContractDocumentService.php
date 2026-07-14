<?php

namespace App\Services;

use App\Enums\SignatureStatus;
use App\Models\Contract;
use App\Models\ContractTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ContractDocumentService
{
    public const MAX_SIGNED_FILE_BYTES = 15 * 1024 * 1024;

    public function generate(Contract $contract, ContractTemplate $template): Contract
    {
        $contract->loadMissing(['tenant', 'property', 'receiver', 'inspectionPhotos']);

        $contractText = $this->renderTemplate($template->content, $this->buildVariables($contract));

        if ($contract->generated_document_path) {
            Storage::disk('local')->delete($contract->generated_document_path);
        }

        $path = "contracts/{$contract->id}/generated-".now()->timestamp.'.pdf';
        $pdf = Pdf::loadView('pdf.contract', [
            'contractText' => $contractText,
            'photos' => $contract->inspectionPhotos,
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

    public function generateReceiptPdf(Contract $contract, \App\Models\Charge $charge): string
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
            'inquilino_documento' => $contract->tenant->document,
            'inquilino_email' => $contract->tenant->email,
            'inquilino_whatsapp' => $contract->tenant->whatsapp ?? '',
            'imovel_nome' => $contract->property->name,
            'imovel_endereco' => $contract->property->address,
            'imovel_tipo' => $contract->property->type,
            'recebedor_nome' => $contract->receiver->name,
            'recebedor_documento' => $contract->receiver->document,
            'valor_aluguel' => 'R$ '.number_format((float) $contract->monthly_rent, 2, ',', '.'),
            'dia_vencimento' => (string) $contract->due_day,
            'data_inicio' => $contract->starts_at->format('d/m/Y'),
            'data_fim' => $contract->ends_at->format('d/m/Y'),
            'multa_percentual' => number_format((float) $contract->fine_rate * 100, 0),
            'juros_percentual' => number_format((float) $contract->monthly_interest_rate * 100, 0),
            'carencia_dias' => (string) $contract->grace_days,
            'data_geracao' => now()->timezone('America/Sao_Paulo')->format('d/m/Y'),
        ];
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function renderTemplate(string $content, array $variables): string
    {
        return preg_replace_callback(
            '/{{\s*(\w+)\s*}}/u',
            fn (array $matches) => $variables[$matches[1]] ?? $matches[0],
            $content,
        ) ?? $content;
    }
}
