<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBillingSettingRequest;
use App\Models\BackupSetting;
use App\Models\BillingSetting;
use App\Models\PixSyncSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Renders the whole "Configurações" page — despite the name, this is now
 * the single settings page for more than billing: it also gathers the
 * backup-schedule and Pix-sync-schedule props {@see BackupSettingController}
 * and {@see PixSyncSettingController} need to prefill their forms. Each
 * section posts its update back to its own controller (same pattern as
 * Settings/Profile.vue + DeleteUser), so `edit()` here only ever reads.
 */
class BillingSettingController extends Controller
{
    public function edit(): Response
    {
        $backupSetting = BackupSetting::current();
        $pixSyncSetting = PixSyncSetting::current();

        return Inertia::render('admin/BillingSettings', [
            'generation_day' => BillingSetting::current()->generation_day,
            'backup_frequency' => $backupSetting->frequency->value,
            'backup_retention_count' => $backupSetting->retention_count,
            'backup_run_at_hour' => $backupSetting->run_at_hour,
            'backup_last_run_at' => $backupSetting->last_run_at?->toIso8601String(),
            'pix_sync_enabled' => $pixSyncSetting->enabled,
            'pix_sync_interval_value' => $pixSyncSetting->interval_value,
            'pix_sync_interval_unit' => $pixSyncSetting->interval_unit->value,
            'pix_sync_last_run_at' => $pixSyncSetting->last_run_at?->toIso8601String(),
        ]);
    }

    public function update(UpdateBillingSettingRequest $request): RedirectResponse
    {
        BillingSetting::current()->update($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Configuracao de cobranca atualizada.',
        ]);

        return back();
    }
}
