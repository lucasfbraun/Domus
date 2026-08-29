<?php

namespace App\Http\Requests\Admin;

/**
 * Reuses StoreOwnerRequest's rules and CPF/CNPJ + phone normalization
 * unchanged; the inherited `authorize()` only checks the `create` policy on
 * Owner, so per-owner update authorization is enforced separately by the
 * controller via `$this->authorize('update', $owner)`.
 */
class UpdateOwnerRequest extends StoreOwnerRequest {}
