<?php

namespace App\Http\Requests\Admin;

/**
 * Reuses StoreContractTemplateRequest's rules and HTML sanitization unchanged;
 * `authorize()` only checks that the user is an admin, not ownership of the
 * template being updated.
 */
class UpdateContractTemplateRequest extends StoreContractTemplateRequest {}
