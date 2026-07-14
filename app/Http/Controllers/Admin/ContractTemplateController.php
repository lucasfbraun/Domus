<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContractTemplateRequest;
use App\Http\Requests\Admin\UpdateContractTemplateRequest;
use App\Models\Contract;
use App\Models\ContractTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContractTemplateController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/templates/Index', [
            'templates' => ContractTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/templates/Form', [
            'template' => null,
        ]);
    }

    public function store(StoreContractTemplateRequest $request): RedirectResponse
    {
        ContractTemplate::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modelo cadastrado.']);

        return to_route('admin.templates.index');
    }

    public function edit(ContractTemplate $template): Response
    {
        return Inertia::render('admin/templates/Form', [
            'template' => $template,
        ]);
    }

    public function update(UpdateContractTemplateRequest $request, ContractTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modelo atualizado.']);

        return to_route('admin.templates.index');
    }

    public function destroy(ContractTemplate $template): RedirectResponse
    {
        if (Contract::query()->where('template_id', $template->id)->exists()) {
            return back()->withErrors(['template' => 'Nao e possivel excluir um modelo em uso.']);
        }

        $template->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Modelo removido.']);

        return to_route('admin.templates.index');
    }
}
