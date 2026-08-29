<?php

namespace App\Http\Requests\Admin;

/**
 * Reuses StoreReceiverRequest's rules and CPF/CNPJ normalization unchanged;
 * the inherited `authorize()` only checks the `create` policy on Receiver, so
 * per-receiver update authorization is enforced separately by the controller
 * via `$this->authorize('update', $receiver)`.
 */
class UpdateReceiverRequest extends StoreReceiverRequest {}
