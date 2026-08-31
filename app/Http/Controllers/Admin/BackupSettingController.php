<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBackupSettingRequest;
use App\Models\BackupSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Update-only: the "Configurações" page itself is rendered by
 * {@see BillingSettingController::edit()}, which gathers this setting's
 * props alongside billing's — this form just posts its own slice back,
 * same pattern as Settings\ProfileController + DeleteUser on the profile
 * page.
 */
class BackupSettingController extends Controller
{
    public function update(UpdateBackupSettingRequest $request): RedirectResponse
    {
        BackupSetting::current()->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Configuração de backup automático atualizada.',
        ]);

        return back();
    }
}
