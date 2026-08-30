<?php

namespace App\Http\Requests\Admin;

use App\Models\Owner;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Reuses StoreOwnerRequest's rules and CPF/CNPJ + phone normalization,
 * except for `email`: the store rule's "creating a new login" check would
 * false-positive against the owner's own already-linked user, so it's
 * rebuilt here the same way UpdateTenantRequest/UpdateReceiverRequest
 * handle it — only required when a password is about to create a *new*
 * User (owner has no user_id yet, and isn't linking to an existing one).
 * The inherited `authorize()` only checks the `create` policy on Owner;
 * per-owner update authorization is enforced separately by the controller
 * via `$this->authorize('update', $owner)`.
 */
class UpdateOwnerRequest extends StoreOwnerRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Owner|null $owner */
        $owner = $this->route('owner');

        $creatingNewLogin = filled($this->input('password'))
            && ! filled($this->input('existing_user_id'))
            && ! $owner?->user_id;

        $emailRules = $creatingNewLogin
            ? ['required', 'email', 'max:255', Rule::unique('users', 'email')]
            : ['nullable', 'email', 'max:255'];

        return [
            ...parent::rules(),
            'email' => $emailRules,
            'password' => [
                'nullable',
                'confirmed',
                Password::defaults(),
                Rule::prohibitedIf(fn () => filled($this->input('existing_user_id'))),
            ],
        ];
    }
}
