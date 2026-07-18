<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContractRequest;
use App\Http\Requests\Admin\UpdateContractRequest;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\ContractWitness;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Services\ContractSignatureService;
use App\Services\ReminderService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Contract::class);

        return Inertia::render('admin/contracts/Index', [
            'contracts' => Contract::query()
                ->with(['property', 'tenant', 'receiver', 'witnesses.receiver'])
                ->orderByDesc('starts_at')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Contract::class);

        return Inertia::render('admin/contracts/Form', [
            'contract' => null,
            'properties' => Property::query()->orderBy('name')->get(),
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'receivers' => Receiver::query()->orderBy('name')->get(),
            'templates' => ContractTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $this->authorize('create', Contract::class);

        $contract = Contract::query()->create($request->safe()->except(['witness_receiver_ids']));

        $this->syncWitnesses($contract, $request->input('witness_receiver_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrato cadastrado.']);

        return to_route('admin.contracts.index');
    }

    public function show(Contract $contract, ContractSignatureService $signatureService): Response
    {
        $this->authorize('view', $contract);

        return Inertia::render('admin/contracts/Show', [
            'contract' => $contract->load([
                'property.owners',
                'tenant',
                'receiver',
                'template',
                'witnesses.receiver',
                'charges',
                'inspectionPhotos',
                'occurrences.photos',
            ]),
            'templates' => ContractTemplate::query()->orderBy('name')->get(['id', 'name']),
            'readyForTenantSignature' => $signatureService->isContractReadyForTenantSignature($contract),
        ]);
    }

    public function edit(Contract $contract): Response
    {
        $this->authorize('update', $contract);

        return Inertia::render('admin/contracts/Form', [
            'contract' => $contract->load('witnesses'),
            'properties' => Property::query()->orderBy('name')->get(),
            'tenants' => Tenant::query()->orderBy('name')->get(),
            'receivers' => Receiver::query()->orderBy('name')->get(),
            'templates' => ContractTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateContractRequest $request, Contract $contract, ReminderService $reminderService): RedirectResponse
    {
        $this->authorize('update', $contract);

        $previousStatus = $contract->status;
        $contract->update($request->safe()->except(['witness_receiver_ids']));

        $this->syncWitnesses($contract, $request->input('witness_receiver_ids', []));

        if ($previousStatus !== ContractStatus::Expiring && $contract->status === ContractStatus::Expiring) {
            $reminderService->sendContractExpiringReminder($contract);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrato atualizado.']);

        return to_route('admin.contracts.show', $contract);
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->authorize('delete', $contract);

        $contract->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Contrato removido.']);

        return to_route('admin.contracts.index');
    }

    public function attachWitness(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $request->validate(['receiver_id' => ['required', 'integer', 'exists:receivers,id']]);

        ContractWitness::query()->firstOrCreate([
            'contract_id' => $contract->id,
            'receiver_id' => $request->integer('receiver_id'),
        ]);

        return back();
    }

    public function markOwnerSigned(Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $contract->update(['owner_signed_at' => now()]);

        return back();
    }

    public function markWitnessSigned(Contract $contract, ContractWitness $witness): RedirectResponse
    {
        $this->authorize('update', $contract);
        abort_unless($witness->contract_id === $contract->id, 404);

        $witness->update(['signed_at' => now()]);

        return back();
    }

    /**
     * @param  list<int>  $receiverIds
     */
    private function syncWitnesses(Contract $contract, array $receiverIds): void
    {
        $receiverIds = array_values(array_unique(array_map('intval', $receiverIds)));

        ContractWitness::query()
            ->where('contract_id', $contract->id)
            ->whereNotIn('receiver_id', $receiverIds)
            ->delete();

        foreach ($receiverIds as $receiverId) {
            ContractWitness::query()->firstOrCreate([
                'contract_id' => $contract->id,
                'receiver_id' => $receiverId,
            ]);
        }
    }
}
