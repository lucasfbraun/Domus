<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Receiver;
use App\Services\PortalAccountService;
use Illuminate\Validation\Rule;

/**
 * Reuses StoreReceiverRequest's rules and CPF/CNPJ normalization, except for
 * `email`: the store rule's blanket "unique across users" check would false-
 * positive against the receiver's own already-linked user, so it's rebuilt
 * here — required against `users.email` when a password is about to create
 * a *new* User (receiver has no user_id yet), or when the receiver's login
 * is already linked but exclusively its own (PortalAccountService::sync()
 * will write this email onto it in that case; see isExclusiveLogin()).
 * Neither check applies when linking to a *different* existing user, since
 * that user's own email/name are left untouched.
 * The inherited `authorize()` only checks the `create` policy on Receiver;
 * per-receiver update authorization is enforced separately by the
 * controller via `$this->authorize('update', $receiver)`.
 */
class UpdateReceiverRequest extends StoreReceiverRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Receiver|null $receiver */
        $receiver = $this->route('receiver');
        $portalAccounts = app(PortalAccountService::class);

        $existingUserId = $this->integer('existing_user_id') ?: null;
        $linkingToAnotherUser = $existingUserId !== null && $existingUserId !== $receiver?->user_id;

        $emailRules = ['required', 'email', 'max:255'];

        if (filled($this->input('password')) && ! filled($this->input('existing_user_id')) && ! $receiver?->user_id) {
            $emailRules[] = Rule::unique('users', 'email');
        } elseif (! $linkingToAnotherUser && $receiver?->user_id && $portalAccounts->isExclusiveLogin($receiver->user_id, UserRole::Receiver)) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($receiver->user_id);
        }

        return [
            ...parent::rules(),
            'email' => $emailRules,
        ];
    }
}
