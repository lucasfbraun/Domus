# Tenant pre-registration: per-invite link, no password in the public form, forced temporary password on approval

**Status**: accepted

The admin wanted prospective tenants to self-report their own onboarding data (name, document, e-mail, WhatsApp, resident count) via a link, instead of the admin retyping it, with a review step before the tenant can access anything.

Three decisions shaped this, each confirmed with the admin before implementing (the trade-offs were real enough to be worth asking rather than guessing):

## One link per invite, not a generic public form

Each invite is its own `TenantPreRegistration` row with a unique token, created by an explicit admin action ("gerar link"), not a single reusable `/cadastre-se` URL. This means every submission traces back to a specific admin decision to invite that person, at a specific time — there's no way for a stranger to submit an unsolicited application, and the admin's pending-invites list is inherently scoped to people they actually intend to onboard.

## The public form collects no password

Only the five fields the admin listed (name, document, e-mail, WhatsApp, resident count) — no password field. Password/login only comes into existence at **approval**, not at submission. This keeps the public, unauthenticated form as narrow as possible (less to validate, less that can go wrong before a human reviews it) and means a pending/rejected pre-registration never has a real login sitting around — `TenantPreRegistrationService::approve()` is the only path that creates a `User`.

## Approval creates the User with a fixed temporary password, forced to change on first login

`TenantPreRegistrationService::approve()` creates the Tenant and its User with a fixed password (`Muda@123`) rather than a random generated one or an emailed set-password link, because the admin will typically hand the tenant this password directly (in person, by phone) at the same time as finishing the paperwork — a random password would just mean re-typing or re-reading it out, and an emailed reset link depends on the tenant's inbox being reachable at that exact moment. New column `users.must_change_password` (boolean, default false) is set `true` on approval, and `App\Http\Middleware\EnsureUserHasChangedPassword` — registered in the same `auth`+`verified` route group everything else sits in — redirects any request from a flagged user to `settings/security` until they change it, with an explicit exemption list (`security.edit`, `user-password.update`, the Fortify password-confirmation routes, and `logout`) so the redirect itself is reachable and a stuck user can always sign out. `SecurityController::update()` clears the flag the moment a real password is set.

This is the same mechanism a future "admin resets a user's password" feature would want, so `must_change_password` is a plain column on `User`, not something specific to tenants or to pre-registration.

**Addendum**: that future feature arrived — the Tenant create/edit form has an "Exigir troca de senha no próximo login" checkbox next to the password field. The setter was extracted from the inline query-builder update above into `PortalAccountService::forcePasswordChangeOnNextLogin()` (still a raw query builder call under the hood, for the same reason: the column stays off `User`'s Fillable list on purpose, so it can only ever be flipped from an explicit, reviewed call site, never through mass assignment) — `approve()` here now calls that shared method instead of duplicating the update. `TenantController` only calls it when a password was actually submitted alongside the checkbox; checking the box with no new password is a no-op, since there'd be no new password to force the tenant away from.

## Token storage: plain, not hashed

The token is stored as-is (not hashed like Laravel's own password-reset tokens). A leaked pre-registration link only exposes the applicant's own self-reported, not-yet-reviewed data — nothing pre-existing, nothing another Tenant/User can already see — and every value it can produce still requires an explicit admin approval before it does anything. That's a low enough blast radius that hashing at rest wasn't worth the extra complexity here; revisit if a future version starts storing more sensitive intake data before review.
