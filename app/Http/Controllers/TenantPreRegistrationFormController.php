<?php

namespace App\Http\Controllers;

use App\Enums\PreRegistrationStatus;
use App\Http\Requests\StoreTenantPreRegistrationSubmissionRequest;
use App\Models\TenantPreRegistration;
use App\Services\TenantPreRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

/**
 * Public, unauthenticated form a prospective tenant fills in from the link
 * an admin generated for them — see
 * docs/adr/0009-tenant-pre-registration.md. No auth/verified middleware:
 * the token in the URL is the only credential.
 */
class TenantPreRegistrationFormController extends Controller
{
    public function show(string $token): Response
    {
        $preRegistration = $this->findByToken($token);

        return Inertia::render('auth/PreCadastro', [
            'status' => $this->publicStatus($preRegistration),
            'name' => $preRegistration->name,
            'token' => $token,
        ]);
    }

    public function store(string $token, StoreTenantPreRegistrationSubmissionRequest $request, TenantPreRegistrationService $service): RedirectResponse
    {
        $preRegistration = $this->findByToken($token);

        try {
            $service->submit($preRegistration, $request->validated());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['form' => $exception->getMessage()]);
        }

        return to_route('tenant-pre-registrations.show', $token);
    }

    private function findByToken(string $token): TenantPreRegistration
    {
        $preRegistration = TenantPreRegistration::query()->where('token', $token)->first();

        abort_if(! $preRegistration, 404);

        return $preRegistration;
    }

    /**
     * @return 'fillable'|'expired'|'submitted'|'approved'|'rejected'
     */
    private function publicStatus(TenantPreRegistration $preRegistration): string
    {
        if ($preRegistration->status === PreRegistrationStatus::Pending && $preRegistration->isExpired()) {
            return 'expired';
        }

        return match ($preRegistration->status) {
            PreRegistrationStatus::Pending => 'fillable',
            PreRegistrationStatus::InReview => 'submitted',
            PreRegistrationStatus::Approved => 'approved',
            PreRegistrationStatus::Rejected => 'rejected',
        };
    }
}
