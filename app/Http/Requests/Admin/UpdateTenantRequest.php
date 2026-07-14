<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTenantRequest extends StoreTenantRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'email' => ['required', 'email', 'max:255', Rule::unique('tenants', 'email')->ignore($this->route('tenant'))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
