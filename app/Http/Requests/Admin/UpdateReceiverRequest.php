<?php

namespace App\Http\Requests\Admin;

use App\Models\Receiver;
use Illuminate\Validation\Rule;

/**
 * Reuses StoreReceiverRequest's rules and CPF/CNPJ normalization, except for
 * `email`: the store rule's blanket "unique across users" check would false-
 * positive against the receiver's own already-linked user, so it's rebuilt
 * here the same way UpdateTenantRequest handles it — only required when a
 * password is about to create a *new* User (receiver has no user_id yet).
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

        $emailRules = ['required', 'email', 'max:255'];

        if (filled($this->input('password')) && ! $receiver?->user_id) {
            $emailRules[] = Rule::unique('users', 'email');
        }

        return [
            ...parent::rules(),
            'email' => $emailRules,
        ];
    }
}
