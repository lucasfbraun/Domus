<?php

namespace App\Http\Requests\Admin;

/**
 * Reuses StorePropertyRequest's rules and `owner_ids` normalization unchanged;
 * the inherited `authorize()` only checks the `create` policy on Property, so
 * per-property update authorization is enforced separately by the controller
 * via `$this->authorize('update', $property)`.
 */
class UpdatePropertyRequest extends StorePropertyRequest {}
