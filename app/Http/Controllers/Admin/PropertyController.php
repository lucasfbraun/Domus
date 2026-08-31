<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PropertyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePropertyRequest;
use App\Http\Requests\Admin\UpdatePropertyRequest;
use App\Models\Owner;
use App\Models\Property;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Property::class);

        return Inertia::render('admin/properties/Index', [
            'properties' => Property::query()
                ->with(['owners', 'media'])
                ->orderBy('name')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString()
                ->through(fn (Property $property) => $this->propertyPayload($property)),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Property::class);

        return Inertia::render('admin/properties/Form', [
            'property' => null,
            'owners' => Owner::query()->orderBy('name')->get(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $this->authorize('create', Property::class);

        $property = Property::query()->create($request->safe()->except(['owner_ids', 'photo']));
        $property->owners()->sync($request->input('owner_ids', []));
        $this->syncCoverPhoto($request, $property);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Imovel cadastrado.']);

        return to_route('admin.properties.index');
    }

    public function edit(Property $property): Response
    {
        $this->authorize('update', $property);

        $property->load(['owners', 'media']);

        return Inertia::render('admin/properties/Form', [
            'property' => $this->propertyPayload($property),
            'owners' => Owner::query()->orderBy('name')->get(),
            'types' => $this->typeOptions(),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $this->authorize('update', $property);

        $property->update($request->safe()->except(['owner_ids', 'photo', 'remove_photo']));
        $property->owners()->sync($request->input('owner_ids', []));
        $this->syncCoverPhoto($request, $property);

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

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     address: string,
     *     type: string,
     *     type_label: string,
     *     status: string,
     *     cover_url: string|null,
     *     owners: array<int, array{id: int, name: string}>
     * }
     */
    private function propertyPayload(Property $property): array
    {
        return [
            'id' => $property->id,
            'name' => $property->name,
            'address' => $property->address,
            'type' => $property->type->value,
            'type_label' => $property->type->label(),
            'status' => $property->status->value,
            'cover_url' => $property->coverUrl(),
            'owners' => $property->owners
                ->map(fn (Owner $owner) => [
                    'id' => $owner->id,
                    'name' => $owner->name,
                ])
                ->values()
                ->all(),
        ];
    }

    private function syncCoverPhoto(StorePropertyRequest $request, Property $property): void
    {
        if ($request->hasFile('photo')) {
            $property
                ->addMediaFromRequest('photo')
                ->toMediaCollection(Property::COVER_COLLECTION);

            return;
        }

        if ($request->boolean('remove_photo')) {
            $property->clearMediaCollection(Property::COVER_COLLECTION);
        }
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return array_map(
            fn (PropertyType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            PropertyType::cases(),
        );
    }
}
