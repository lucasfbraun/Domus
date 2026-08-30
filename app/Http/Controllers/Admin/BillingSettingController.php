<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBillingSettingRequest;
use App\Models\BillingSetting;
use App\Services\ChargeScheduler;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Single-row settings page: the day of the month the automatic monthly
 * charge sweep ({@see ChargeScheduler::runMonthlyChargeSweep()})
 * is allowed to start generating charges. Each Contract's own due date is
 * unaffected by this setting.
 */
class BillingSettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/BillingSettings', [
            'generation_day' => BillingSetting::current()->generation_day,
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
