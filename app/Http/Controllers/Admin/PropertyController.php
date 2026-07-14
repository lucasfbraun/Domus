<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePropertyRequest;
use App\Http\Requests\Admin\UpdatePropertyRequest;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Property::class);

        return Inertia::render('admin/properties/Index', [
            'properties' => Property::query()->with('owner')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Property::class);

        return Inertia::render('admin/properties/Form', [
            'property' => null,
            'owners' => Owner::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $this->authorize('create', Property::class);

        Property::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Imovel cadastrado.']);

        return to_route('admin.properties.index');
    }

    public function edit(Property $property): Response
    {
        $this->authorize('update', $property);

        return Inertia::render('admin/properties/Form', [
            'property' => $property->load('owner'),
            'owners' => Owner::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $property->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Imovel atualizado.']);

        return to_route('admin.properties.index');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->authorize('delete', $property);

        $property->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Imovel removido.']);

        return to_route('admin.properties.index');
    }
}
