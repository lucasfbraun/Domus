<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Owner;
use App\Services\PortalAccountService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Reuses StoreOwnerRequest's rules and CPF/CNPJ + phone normalization,
 * except for `email`: the store rule's "creating a new login" check would
 * false-positive against the owner's own already-linked user, so it's
 * rebuilt here — required against `users.email` when a password is about to
 * create a *new* User (owner has no user_id yet, and isn't linking to an
 * existing one), or when the owner's login is already linked but
 * exclusively its own (PortalAccountService::sync() will write this email
 * onto it in that case; see isExclusiveLogin()). Neither check applies when
 * linking to a *different* existing user, since that user's own email/name
 * are left untouched.
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
        $portalAccounts = app(PortalAccountService::class);

        $existingUserId = $this->integer('existing_user_id') ?: null;
        $linkingToAnotherUser = $existingUserId !== null && $existingUserId !== $owner?->user_id;

        $creatingNewLogin = filled($this->input('password'))
            && ! filled($this->input('existing_user_id'))
            && ! $owner?->user_id;

        $syncingIntoExclusiveLogin = ! $linkingToAnotherUser
            && $owner?->user_id
            && $portalAccounts->isExclusiveLogin($owner->user_id, UserRole::Owner);

        $emailRules = match (true) {
            $creatingNewLogin => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            $syncingIntoExclusiveLogin => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($owner->user_id)],
            default => ['nullable', 'email', 'max:255'],
        };

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
