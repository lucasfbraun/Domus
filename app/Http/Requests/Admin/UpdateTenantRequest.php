<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Services\PortalAccountService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTenantRequest extends StoreTenantRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Tenant|null $tenant */
        $tenant = $this->route('tenant');
        $portalAccounts = app(PortalAccountService::class);

        $emailRules = ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($tenant)];

        if (filled($this->input('password')) && ! $tenant?->user_id) {
            // First-time password: TenantController is about to create a
            // brand-new dedicated User with this email.
            $emailRules[] = Rule::unique('users', 'email');
        } elseif ($tenant?->user_id && $portalAccounts->isExclusiveLogin($tenant->user_id, UserRole::Tenant)) {
            // Already linked to a login that's exclusively this tenant's:
            // PortalAccountService::sync() will write this email onto it,
            // so it needs to stay unique across users.email too.
            $emailRules[] = Rule::unique('users', 'email')->ignore($tenant->user_id);
        }

        return [
            ...parent::rules(),
            'email' => $emailRules,
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
