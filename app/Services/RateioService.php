<?php

namespace App\Services;

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Enums\RateioSplitMode;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Property;
use App\Models\Rateio;
use App\Models\RateioAllocation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Splits a shared expense (Rateio) across properties and folds each
 * property's share directly into that property's own Contract's Charge
 * for the matching reference month (see CONTEXT.md's Rateio entry — this
 * never creates a separate Charge). `update()`/`delete()` must always
 * {@see reverseAppliedAllocations()} before recomputing or removing
 * allocations — applying a new split without first undoing the old one
 * would double-count the previous share still sitting inside
 * `Charge::original_amount`/`rateio_amount`. Both also refuse to touch a
 * Rateio already linked to a *paid* Charge ({@see assertNoLinkedPaidCharge()}),
 * since a paid Charge shouldn't have its amount silently rewritten.
 */
class RateioService
{
    public const CATEGORIES = ['agua', 'condominio', 'gas', 'internet', 'iptu', 'outro'];

    public const ACCEPTED_INVOICE_CONTENT_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

    public const MAX_INVOICE_BYTES = 8 * 1024 * 1024;

    /**
     * @param  list<int>  $propertyIds
     * @return array{rateio: Rateio, appliedCount: int, pendingCount: int, amountsByProperty: array<int, float>}
     */
    public function create(array $input, ?UploadedFile $invoice = null): array
    {
        return DB::transaction(function () use ($input, $invoice) {
            $propertyIds = $this->uniquePropertyIds($input['property_ids'] ?? []);
            $this->validateInput($input, $propertyIds);

            $rateio = Rateio::query()->create([
                'category' => trim($input['category']),
                'description' => $input['description'] ?? null,
                'reference' => trim($input['reference']),
                'total_amount' => $input['total_amount'],
                'split_mode' => $input['split_mode'] ?? RateioSplitMode::Equal,
                ...$this->storeInvoice($invoice, null),
            ]);

            return $this->computeAndApplyAllocations($rateio, $propertyIds);
        });
    }

    /**
     * @param  list<int>  $propertyIds
     * @return array{rateio: Rateio, appliedCount: int, pendingCount: int, amountsByProperty: array<int, float>}
     */
    public function update(Rateio $rateio, array $input, ?UploadedFile $invoice = null): array
    {
        return DB::transaction(function () use ($rateio, $input, $invoice) {
            $this->assertNoLinkedPaidCharge($rateio);
            $propertyIds = $this->uniquePropertyIds($input['property_ids'] ?? []);
            $this->validateInput($input, $propertyIds);

            $this->reverseAppliedAllocations($rateio);
            $rateio->allocations()->delete();

            $invoiceData = $this->storeInvoice($invoice, $rateio);

            $rateio->update([
                'category' => trim($input['category']),
                'description' => $input['description'] ?? null,
                'reference' => trim($input['reference']),
                'total_amount' => $input['total_amount'],
                'split_mode' => $input['split_mode'] ?? RateioSplitMode::Equal,
                ...$invoiceData,
            ]);

            return $this->computeAndApplyAllocations($rateio->fresh(), $propertyIds);
        });
    }

    public function delete(Rateio $rateio): void
    {
        DB::transaction(function () use ($rateio) {
            $this->assertNoLinkedPaidCharge($rateio);
            $this->reverseAppliedAllocations($rateio);

            if ($rateio->invoice_path) {
                Storage::disk('local')->delete($rateio->invoice_path);
            }

            $rateio->allocations()->delete();
            $rateio->delete();
        });
    }

    public function applyPendingRateioAllocations(int $propertyId, string $reference, Charge $charge): bool
    {
        $pending = RateioAllocation::query()
            ->where('property_id', $propertyId)
            ->whereNull('applied_at')
            ->whereHas('rateio', fn ($query) => $query->where('reference', $reference))
            ->get();

        if ($pending->isEmpty()) {
            return false;
        }

        $total = Finance::roundCents($pending->sum('amount'));

        $charge->update([
            'original_amount' => $charge->original_amount + $total,
            'rateio_amount' => ($charge->rateio_amount ?? 0) + $total,
            'mercado_pago_order_id' => null,
            'mercado_pago_transaction_id' => null,
            'payment_url' => null,
            'pix_qr_code' => null,
            'pix_qr_code_base64' => null,
            'pix_expires_at' => null,
        ]);

        $now = now();
        foreach ($pending as $allocation) {
            $allocation->update([
                'applied_at' => $now,
                'charge_id' => $charge->id,
            ]);
        }

        return true;
    }

    /**
     * @param  list<int>  $propertyIds
     * @return array{rateio: Rateio, appliedCount: int, pendingCount: int, amountsByProperty: array<int, float>}
     */
    private function computeAndApplyAllocations(Rateio $rateio, array $propertyIds): array
    {
        $weights = $this->buildWeights($propertyIds, $rateio->split_mode);
        $amountsByProperty = Finance::splitByWeights((float) $rateio->total_amount, $weights);
        $appliedCount = 0;

        foreach ($propertyIds as $propertyId) {
            RateioAllocation::query()->create([
                'rateio_id' => $rateio->id,
                'property_id' => $propertyId,
                'amount' => $amountsByProperty[(string) $propertyId] ?? 0,
            ]);

            if ($this->tryApplyAllocation($propertyId, $rateio->reference)) {
                $appliedCount++;
            }
        }

        return [
            'rateio' => $rateio->load('allocations.property'),
            'appliedCount' => $appliedCount,
            'pendingCount' => count($propertyIds) - $appliedCount,
            'amountsByProperty' => array_map(
                fn ($id) => $amountsByProperty[(string) $id] ?? 0,
                $propertyIds,
            ),
        ];
    }

    /**
     * @param  list<int>  $propertyIds
     * @return list<array{key: string, weight: float|int}>
     */
    private function buildWeights(array $propertyIds, RateioSplitMode $splitMode): array
    {
        if ($splitMode === RateioSplitMode::Equal) {
            return array_map(
                fn (int $propertyId) => ['key' => (string) $propertyId, 'weight' => 1],
                $propertyIds,
            );
        }

        $residentInfo = $this->getResidentInfoForProperties($propertyIds);

        return array_map(
            fn (int $propertyId) => [
                'key' => (string) $propertyId,
                'weight' => $residentInfo[$propertyId]['resident_count'] ?? 1,
            ],
            $propertyIds,
        );
    }

    /**
     * @param  list<int>  $propertyIds
     * @return array<int, array{property_id: int, resident_count: int|null, tenant_name: string|null}>
     */
    public function getResidentInfoForProperties(array $propertyIds): array
    {
        $result = [];

        $contracts = Contract::query()
            ->with('tenant')
            ->whereIn('property_id', $propertyIds)
            ->whereIn('status', [ContractStatus::Active, ContractStatus::Expiring])
            ->get()
            ->keyBy('property_id');

        foreach ($propertyIds as $propertyId) {
            $contract = $contracts->get($propertyId);
            $result[$propertyId] = [
                'property_id' => $propertyId,
                'resident_count' => $contract?->tenant?->resident_count,
                'tenant_name' => $contract?->tenant?->name,
            ];
        }

        return $result;
    }

    private function tryApplyAllocation(int $propertyId, string $reference): bool
    {
        $contract = Contract::query()
            ->where('property_id', $propertyId)
            ->whereIn('status', [ContractStatus::Active, ContractStatus::Expiring])
            ->first();

        if (! $contract) {
            return false;
        }

        $charge = Charge::query()
            ->where('contract_id', $contract->id)
            ->where('reference', $reference)
            ->where('status', '!=', ChargeStatus::Cancelled)
            ->first();

        if (! $charge) {
            return false;
        }

        return $this->applyPendingRateioAllocations($propertyId, $reference, $charge);
    }

    private function reverseAppliedAllocations(Rateio $rateio): void
    {
        $rateio->load('allocations.charge');

        foreach ($rateio->allocations as $allocation) {
            if (! $allocation->applied_at || ! $allocation->charge_id) {
                continue;
            }

            $charge = $allocation->charge;
            if (! $charge) {
                continue;
            }

            $charge->update([
                'original_amount' => max(0, $charge->original_amount - $allocation->amount),
                'rateio_amount' => max(0, ($charge->rateio_amount ?? 0) - $allocation->amount),
                'mercado_pago_order_id' => null,
                'mercado_pago_transaction_id' => null,
                'payment_url' => null,
                'pix_qr_code' => null,
                'pix_qr_code_base64' => null,
                'pix_expires_at' => null,
            ]);
        }
    }

    private function assertNoLinkedPaidCharge(Rateio $rateio): void
    {
        $hasPaid = $rateio->allocations()
            ->whereHas('charge', fn ($query) => $query->where('status', ChargeStatus::Paid))
            ->exists();

        if ($hasPaid) {
            throw new \InvalidArgumentException(
                'Nao e possivel editar ou excluir: este rateio ja esta aplicado a uma cobranca paga.',
            );
        }
    }

    /**
     * @param  list<int>  $propertyIds
     */
    private function validateInput(array $input, array $propertyIds): void
    {
        if (empty(trim($input['category'] ?? ''))) {
            throw new \InvalidArgumentException('Informe a categoria do rateio.');
        }

        if (empty(trim($input['reference'] ?? ''))) {
            throw new \InvalidArgumentException('Informe o mes/ano de referencia.');
        }

        if ($propertyIds === []) {
            throw new \InvalidArgumentException('Selecione ao menos um imovel para o rateio.');
        }

        if (! isset($input['total_amount']) || $input['total_amount'] <= 0) {
            throw new \InvalidArgumentException('Informe um valor total valido para o rateio.');
        }
    }

    /**
     * @param  list<int>  $propertyIds
     * @return list<int>
     */
    private function uniquePropertyIds(array $propertyIds): array
    {
        return array_values(array_unique(array_map('intval', $propertyIds)));
    }

    /**
     * @return array{invoice_path?: string|null, invoice_content_type?: string|null, invoice_file_name?: string|null}
     */
    private function storeInvoice(?UploadedFile $invoice, ?Rateio $existing): array
    {
        if (! $invoice) {
            return [];
        }

        if (! in_array($invoice->getMimeType(), self::ACCEPTED_INVOICE_CONTENT_TYPES, true)) {
            throw new \InvalidArgumentException('Formato nao suportado. Envie o comprovante em JPG, PNG ou PDF.');
        }

        if ($invoice->getSize() > self::MAX_INVOICE_BYTES) {
            throw new \InvalidArgumentException('Arquivo muito grande (limite de 8MB).');
        }

        if ($existing?->invoice_path) {
            Storage::disk('local')->delete($existing->invoice_path);
        }

        $path = $invoice->store('rateios', 'local');

        return [
            'invoice_path' => $path,
            'invoice_content_type' => $invoice->getMimeType(),
            'invoice_file_name' => $invoice->getClientOriginalName(),
        ];
    }
}
