<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a user flagged with {@see User::$must_change_password} (set when
 * `TenantPreRegistrationService::approve()` creates a login with the fixed
 * temporary password) to the security settings page before reaching
 * anything else. See docs/adr/0009-tenant-pre-registration.md.
 *
 * The exempt route list must cover every route needed to actually change
 * the password and to sign out — otherwise a flagged user gets stuck in a
 * redirect loop with no way out.
 */
class EnsureUserHasChangedPassword
{
    private const EXEMPT_ROUTES = [
        'security.edit',
        'user-password.update',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->must_change_password && ! $request->routeIs(...self::EXEMPT_ROUTES)) {
            return redirect()->route('security.edit');
        }

        return $next($request);
    }
}
