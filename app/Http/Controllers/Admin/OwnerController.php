<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOwnerRequest;
use App\Http\Requests\Admin\UpdateOwnerRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Policies\OwnerPolicy;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages Owner records (property owners). See
 * {@see OwnerPolicy}: admin-only — owners never have their own
 * User/login, so there is no owner-facing counterpart to these routes.
 */
class OwnerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Owner::class);

        return Inertia::render('admin/owners/Index', [
            'owners' => Owner::query()
                ->withCount('properties')
                ->orderBy('name')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Owner::class);

        return Inertia::render('admin/owners/Form', [
            'owner' => null,
            'properties' => Property::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreOwnerRequest $request): RedirectResponse
    {
        $this->authorize('create', Owner::class);

        $owner = Owner::query()->create($request->safe()->only(['name', 'document', 'email', 'phone']));
        $owner->properties()->sync($request->input('property_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proprietario cadastrado.']);

        return to_route('admin.owners.index');
    }

    public function edit(Owner $owner): Response
    {
        $this->authorize('update', $owner);

        return Inertia::render('admin/owners/Form', [
            'owner' => $owner->load('properties'),
            'properties' => Property::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateOwnerRequest $request, Owner $owner): RedirectResponse
    {
        $this->authorize('update', $owner);

        $owner->update($request->safe()->only(['name', 'document', 'email', 'phone']));
        $owner->properties()->sync($request->input('property_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proprietario atualizado.']);

        return to_route('admin.owners.index');
    }

    public function destroy(Owner $owner): RedirectResponse
    {
        $this->authorize('delete', $owner);

        $owner->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Proprietario removido.']);

        return to_route('admin.owners.index');
    }
}
