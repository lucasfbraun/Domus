<?php

namespace App\Http\Requests\Admin;

use App\Models\Tenant;
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

        $emailRules = ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($tenant)];

        // Only a first-time password creates a new User (TenantController
        // only inserts one when the tenant has no user_id yet); once linked,
        // re-submitting a password just updates that user's password and
        // never touches users.email, so no uniqueness check is needed then.
        if (filled($this->input('password')) && ! $tenant?->user_id) {
            $emailRules[] = Rule::unique('users', 'email');
        }

        return [
            ...parent::rules(),
            'email' => $emailRules,
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
