<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectTenantPreRegistrationRequest;
use App\Models\TenantPreRegistration;
use App\Services\TenantPreRegistrationService;
use App\Support\Pagination;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin-only management of tenant pre-registration invites. There is no
 * TenantPreRegistrationPolicy — like {@see AdminUserController}, access is
 * gated only by the `role:admin` route middleware.
 */
class TenantPreRegistrationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/tenant-pre-registrations/Index', [
            'preRegistrations' => TenantPreRegistration::query()
                ->with('tenant')
                ->latest('invited_at')
                ->paginate(Pagination::PER_PAGE)
                ->withQueryString()
                ->through(fn (TenantPreRegistration $pre) => [
                    'id' => $pre->id,
                    'status' => $pre->status->value,
                    'name' => $pre->name,
                    'document' => $pre->document,
                    'email' => $pre->email,
                    'whatsapp' => $pre->whatsapp,
                    'resident_count' => $pre->resident_count,
                    'invited_at' => $pre->invited_at->toIso8601String(),
                    'expires_at' => $pre->expires_at->toIso8601String(),
                    'submitted_at' => $pre->submitted_at?->toIso8601String(),
                    'is_expired' => $pre->isExpired(),
                    'rejection_note' => $pre->rejection_note,
                    'tenant_id' => $pre->tenant_id,
                    'link' => $pre->status->value === 'pending' ? URL::to('/pre-cadastro/'.$pre->token) : null,
                ]),
        ]);
    }

    public function store(TenantPreRegistrationService $service): RedirectResponse
    {
        $preRegistration = $service->invite();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Link de pre-cadastro gerado: '.URL::to('/pre-cadastro/'.$preRegistration->token),
        ]);

        return back();
    }

    public function approve(TenantPreRegistration $preRegistration, TenantPreRegistrationService $service): RedirectResponse
    {
        try {
            $service->approve($preRegistration, request()->user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['approve' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pre-cadastro aceito. Inquilino criado.']);

        return back();
    }

    public function reject(RejectTenantPreRegistrationRequest $request, TenantPreRegistration $preRegistration, TenantPreRegistrationService $service): RedirectResponse
    {
        try {
            $service->reject($preRegistration, $request->user(), $request->validated('note'));
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['reject' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pre-cadastro recusado.']);

        return back();
    }
}
