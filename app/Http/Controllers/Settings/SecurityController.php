<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SecurityController extends Controller
{
    /**
     * Show the user's security settings page.
     */
    public function edit(TwoFactorAuthenticationRequest $request): Response
    {
        $props = [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ];

        return Inertia::render('settings/Security', $props);
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->password,
        ]);

        // must_change_password isn't in User's Fillable list (it's a
        // trusted-only flag — see docs/adr/0009-tenant-pre-registration.md).
        // Setting it through the query builder here (rather than folding it
        // into the update() above) is deliberate: the model update() is what
        // applies the `hashed` cast to the password, and a query-builder
        // update() bypasses Eloquent casts entirely — mixing the two in one
        // call would silently store the password in plain text.
        User::query()->whereKey($request->user()->id)->update(['must_change_password' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
