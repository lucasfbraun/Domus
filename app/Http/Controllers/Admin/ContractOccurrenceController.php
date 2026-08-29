<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OccurrenceStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContractOccurrenceRequest;
use App\Http\Requests\Admin\UpdateContractOccurrenceRequest;
use App\Mail\OccurrenceReportedMail;
use App\Mail\OccurrenceUpdatedMail;
use App\Models\Contract;
use App\Models\ContractOccurrence;
use App\Models\ContractOccurrencePhoto;
use App\Models\User;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manages tenant-reported occurrences (maintenance/complaint reports) on a
 * contract, with attached photos. There is no dedicated Policy: `store` and
 * `showPhoto` are reached from the tenant portal and authorize access inline
 * via `abort_unless` (matching the authenticated user's Tenant record),
 * while `index`/`update` are admin-only routes. `store` emails every Admin
 * user via {@see OccurrenceReportedMail}; `update` emails the
 * tenant via {@see OccurrenceUpdatedMail} when the tenant has an
 * email on file.
 */
class ContractOccurrenceController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/occurrences/Index', [
            'occurrences' => ContractOccurrence::query()
                ->with(['contract.property', 'tenant', 'photos'])
                ->latest()
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString()
                ->through(fn (ContractOccurrence $occurrence) => [
                    'id' => $occurrence->id,
                    'description' => $occurrence->description,
                    'status' => $occurrence->status?->value,
                    'status_label' => $occurrence->status?->label(),
                    'resolution_note' => $occurrence->resolution_note,
                    'resolved_at' => $occurrence->resolved_at?->toIso8601String(),
                    'created_at' => $occurrence->created_at?->toIso8601String(),
                    'tenant' => $occurrence->tenant ? [
                        'id' => $occurrence->tenant->id,
                        'name' => $occurrence->tenant->name,
                    ] : null,
                    'property' => $occurrence->contract?->property ? [
                        'id' => $occurrence->contract->property->id,
                        'name' => $occurrence->contract->property->name,
                    ] : null,
                    'photos' => $occurrence->photos->map(fn (ContractOccurrencePhoto $photo) => [
                        'id' => $photo->id,
                        'file_name' => $photo->file_name,
                        'url' => route('occurrences.photos.show', $photo),
                    ]),
                ]),
        ]);
    }

    public function store(StoreContractOccurrenceRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $tenant = $user->tenant;
        abort_unless($tenant, 403);

        $contract = Contract::query()->findOrFail($request->integer('contract_id'));
        abort_unless($contract->tenant_id === $tenant->id, 403);

        $occurrence = ContractOccurrence::query()->create([
            'contract_id' => $contract->id,
            'tenant_id' => $tenant->id,
            'description' => $request->string('description')->toString(),
            'status' => OccurrenceStatus::Open,
        ]);

        $photos = $request->file('photos', []);
        foreach ($photos as $photo) {
            $path = $photo->store("occurrences/{$occurrence->id}", 'local');

            ContractOccurrencePhoto::query()->create([
                'occurrence_id' => $occurrence->id,
                'storage_path' => $path,
                'file_name' => $photo->getClientOriginalName(),
                'content_type' => $photo->getMimeType(),
            ]);
        }

        $contract->loadMissing('property');
        $adminEmails = User::role(UserRole::Admin)->pluck('email')->filter()->values()->all();

        if ($adminEmails !== []) {
            Mail::to($adminEmails)->send(new OccurrenceReportedMail(
                tenantName: $tenant->name,
                propertyName: $contract->property?->name ?? 'imovel',
                description: $request->string('description')->toString(),
                photoCount: count($photos),
            ));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ocorrencia registrada.']);

        return back();
    }

    public function update(
        UpdateContractOccurrenceRequest $request,
        ContractOccurrence $occurrence,
    ): RedirectResponse {
        $status = $request->enum('status', OccurrenceStatus::class);

        $occurrence->update([
            'status' => $status,
            'resolution_note' => $request->input('resolution_note'),
            'resolved_at' => in_array($status, [OccurrenceStatus::Resolved, OccurrenceStatus::Closed], true)
                ? now()
                : null,
        ]);

        $occurrence->loadMissing(['tenant', 'contract.property']);

        if ($occurrence->tenant?->email) {
            Mail::to($occurrence->tenant->email)->send(new OccurrenceUpdatedMail(
                propertyName: $occurrence->contract?->property?->name ?? 'imovel',
                statusLabel: $status->label(),
                resolutionNote: $request->input('resolution_note'),
            ));
        }

        return back();
    }

    /**
     * Streams an occurrence photo from local storage. Allowed for any Admin,
     * or for the Tenant who owns the occurrence the photo belongs to.
     */
    public function showPhoto(Request $request, ContractOccurrencePhoto $photo): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $photo->loadMissing('occurrence.tenant', 'occurrence.contract');

        $allowed = $user->hasRole(UserRole::Admin)
            || ($user->hasRole(UserRole::Tenant) && $photo->occurrence?->tenant?->user_id === $user->id);

        abort_unless($allowed, 403);
        abort_unless($photo->storage_path && Storage::disk('local')->exists($photo->storage_path), 404);

        return Storage::disk('local')->response(
            $photo->storage_path,
            $photo->file_name,
            ['Content-Type' => $photo->content_type ?? 'application/octet-stream'],
        );
    }
}
