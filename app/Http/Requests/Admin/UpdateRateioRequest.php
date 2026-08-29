<?php

namespace App\Http\Requests\Admin;

/**
 * Reuses StoreRateioRequest's rules unchanged; the inherited `authorize()`
 * only checks the `create` policy on Rateio, so per-rateio update
 * authorization is enforced separately by the controller via
 * `$this->authorize('update', $rateio)`.
 */
class UpdateRateioRequest extends StoreRateioRequest {}
