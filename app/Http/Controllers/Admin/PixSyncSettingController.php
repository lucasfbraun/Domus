<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePixSyncSettingRequest;
use App\Models\PixSyncSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Update-only: the "Configurações" page itself is rendered by
 * {@see BillingSettingController::edit()}, which gathers this setting's
 * props alongside billing's and backup's — this form just posts its own
 * slice back, same pattern as {@see BackupSettingController}.
 */
class PixSyncSettingController extends Controller
{
    public function update(UpdatePixSyncSettingRequest $request): RedirectResponse
    {
        // A plain $request->validated() spread would leave `enabled`
        // untouched whenever the checkbox is unchecked — an unchecked
        // HTML checkbox simply isn't present in the request, so it never
        // reaches `validated()` either. ->boolean() correctly reads that
        // absence as false (same pattern as TenantController's
        // force_password_change checkbox).
        PixSyncSetting::current()->update([
            'enabled' => $request->boolean('enabled'),
            'interval_value' => $request->validated('interval_value'),
            'interval_unit' => $request->validated('interval_unit'),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Configuração de sincronização com o Mercado Pago atualizada.',
        ]);

        return back();
    }
}
