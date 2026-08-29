<?php

namespace App\Http\Requests\Admin;

/**
 * Reuses StoreContractRequest's rules and field normalization unchanged; the
 * inherited `authorize()` only checks the `create` policy on Contract, so
 * per-contract update authorization is enforced separately by the controller
 * via `$this->authorize('update', $contract)`.
 */
class UpdateContractRequest extends StoreContractRequest {}
