<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreContractInspectionPhotoRequest;
use App\Models\Contract;
use App\Models\ContractInspectionPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContractInspectionPhotoController extends Controller
{
    public function show(Contract $contract, ContractInspectionPhoto $photo): StreamedResponse
    {
        $this->authorize('view', $contract);
        abort_unless($photo->contract_id === $contract->id, 404);
        abort_unless($photo->storage_path && Storage::disk('local')->exists($photo->storage_path), 404);

        return Storage::disk('local')->response(
            $photo->storage_path,
            $photo->file_name,
            ['Content-Type' => $photo->content_type ?? 'application/octet-stream'],
        );
    }

    public function store(StoreContractInspectionPhotoRequest $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $path = $request->file('photo')->store("contracts/{$contract->id}/inspection", 'local');

        ContractInspectionPhoto::query()->create([
            'contract_id' => $contract->id,
            'storage_path' => $path,
            'file_name' => $request->file('photo')->getClientOriginalName(),
            'content_type' => $request->file('photo')->getMimeType(),
            'caption' => $request->input('caption'),
            'room' => $request->input('room'),
            'position' => $request->input('position', 0),
        ]);

        return back();
    }

    public function destroy(Contract $contract, ContractInspectionPhoto $photo): RedirectResponse
    {
        $this->authorize('update', $contract);
        abort_unless($photo->contract_id === $contract->id, 404);

        Storage::disk('local')->delete($photo->storage_path);
        $photo->delete();

        return back();
    }
}
