<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRateioRequest;
use App\Http\Requests\Admin\UpdateRateioRequest;
use App\Models\Property;
use App\Models\Rateio;
use App\Services\RateioService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RateioController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Rateio::class);

        return Inertia::render('admin/rateios/Index', [
            'rateios' => Rateio::query()
                ->with(['allocations.property'])
                ->latest()
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString(),
            'properties' => Property::query()->orderBy('name')->get(),
            'categories' => RateioService::CATEGORIES,
        ]);
    }

    public function store(StoreRateioRequest $request, RateioService $rateioService): RedirectResponse
    {
        $this->authorize('create', Rateio::class);

        $rateioService->create($request->validated(), $request->file('invoice'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rateio registrado.']);

        return to_route('admin.rateios.index');
    }

    public function update(UpdateRateioRequest $request, Rateio $rateio, RateioService $rateioService): RedirectResponse
    {
        $this->authorize('update', $rateio);

        $rateioService->update($rateio, $request->validated(), $request->file('invoice'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rateio atualizado.']);

        return to_route('admin.rateios.index');
    }

    public function destroy(Rateio $rateio, RateioService $rateioService): RedirectResponse
    {
        $this->authorize('delete', $rateio);

        $rateioService->delete($rateio);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rateio removido.']);

        return to_route('admin.rateios.index');
    }

    public function invoice(Rateio $rateio): BinaryFileResponse
    {
        $this->authorize('view', $rateio);

        abort_unless($rateio->invoice_path, 404);

        return response()->download(
            Storage::disk('local')->path($rateio->invoice_path),
            $rateio->invoice_file_name ?? 'comprovante-rateio',
        );
    }
}
