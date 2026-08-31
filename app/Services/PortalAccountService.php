<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Middleware\EnsureUserHasChangedPassword;
use App\Models\Owner;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Manages the User a Tenant/Receiver/Owner record uses for portal access.
 * A login can be dedicated (created just for that one record) or shared —
 * the same account already used to log in as Admin, or as another
 * Tenant/Receiver/Owner (see docs/adr/0006-shared-login-across-roles.md).
 */
class PortalAccountService
{
    /**
     * Grants `$role` to a user for portal access: reuses `$existingUserId`
     * if given (adding the role is a no-op if already held), otherwise
     * creates a brand-new dedicated User.
     *
     * @param  array{name: string, email: string, password: string}  $newUserData
     */
    public function attach(UserRole $role, array $newUserData, ?int $existingUserId): int
    {
        if ($existingUserId !== null) {
            $user = User::query()->findOrFail($existingUserId);

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            return $user->id;
        }

        $user = User::query()->create([
            'name' => $newUserData['name'],
            'email' => $newUserData['email'],
            'password' => Hash::make($newUserData['password']),
        ]);

        // Nothing in this app ever sends a verification email for a
        // Tenant/Receiver/Owner login — there's no self-registration flow
        // for these roles, an admin is vouching for the address by typing
        // it in here. Leaving this unverified would permanently lock the
        // account out behind the global `verified` middleware with no way
        // to unlock it. (email_verified_at isn't in User's Fillable list,
        // so it can't just be passed into create() above — it's silently
        // dropped by mass-assignment protection if you try.)
        $user->markEmailAsVerified();

        $user->assignRole($role);

        return $user->id;
    }

    /**
     * Resolves a Tenant/Receiver/Owner's `user_id` after a create/update
     * submission. Returns the user_id that should be persisted on the
     * record (null if it still has no portal access).
     *
     * Deliberately does NOT detach the role from whatever user held it
     * before, even when switching to a different existing user — that
     * would check "is any Tenant/Receiver/Owner still pointing at the old
     * user" while *this* record still is, on account of the caller not
     * having persisted the new id yet, and wrongly conclude the old login
     * is still needed. Detaching is on the caller, in this order:
     *
     *     $oldUserId = $record->user_id;
     *     $userId = $portalAccounts->sync(..., currentUserId: $oldUserId, ...);
     *     if ($userId !== $oldUserId) {
     *         $record->update(['user_id' => $userId]);
     *         if ($oldUserId !== null) {
     *             $portalAccounts->detach($oldUserId, $role);
     *         }
     *     }
     *
     * - `$existingUserId` set and different from `$currentUserId`: links to
     *   that user, granting the role.
     * - `$password` given and there's no current user: creates a brand-new
     *   dedicated login.
     * - `$password` given and there IS a current user: just updates that
     *   user's password — email/role are already correct, nothing to sync.
     * - Neither given: no change (also the correct behavior for "just
     *   editing other fields, leave portal access as it is").
     */
    public function sync(
        UserRole $role,
        ?int $currentUserId,
        ?int $existingUserId,
        ?string $name,
        ?string $email,
        ?string $password,
    ): ?int {
        if ($existingUserId !== null && $existingUserId !== $currentUserId) {
            return $this->attach($role, ['name' => '', 'email' => '', 'password' => ''], $existingUserId);
        }

        if (filled($password)) {
            if ($currentUserId === null) {
                return $this->attach($role, [
                    'name' => (string) $name,
                    'email' => (string) $email,
                    'password' => $password,
                ], null);
            }

            User::query()->whereKey($currentUserId)->update(['password' => Hash::make($password)]);

            return $currentUserId;
        }

        return $currentUserId;
    }

    /**
     * Flags a user to be forced through a password change on their next
     * request — see {@see User::$must_change_password} and
     * {@see EnsureUserHasChangedPassword}. Originally
     * only set when a Tenant Pre-cadastro is approved with a fixed
     * temporary password (docs/adr/0009-tenant-pre-registration.md), and
     * reusable from here by any flow — e.g. an admin setting a known
     * password for a Tenant/Receiver/Owner from their edit form.
     *
     * A raw query builder update on purpose: `must_change_password` isn't
     * in User's Fillable list, precisely so it can only ever be set from
     * an explicit, reviewed call site like this one — never through mass
     * assignment from arbitrary request input.
     */
    public function forcePasswordChangeOnNextLogin(int $userId): void
    {
        User::query()->whereKey($userId)->update(['must_change_password' => true]);
    }

    /**
     * Removes `$role`'s claim on a user that a Tenant/Receiver/Owner record
     * no longer references — call this *after* deleting or repointing that
     * record, never before, so the exclusivity check below doesn't count it.
     *
     * If the login exists only to serve that one role (no other role, and
     * no other Tenant/Receiver/Owner points at it), it's deleted entirely —
     * that mirrors the old "dedicated login" behavior. Otherwise the login
     * is shared, so only the role is stripped; the account and whatever
     * else it can still do (log in as Admin, etc.) stay intact.
     */
    public function detach(int $userId, UserRole $role): void
    {
        $user = User::query()->find($userId);

        if (! $user) {
            return;
        }

        $isExclusive = $user->roles->pluck('name')->all() === [$role->value]
            && ! Tenant::query()->where('user_id', $userId)->exists()
            && ! Receiver::query()->where('user_id', $userId)->exists()
            && ! Owner::query()->where('user_id', $userId)->exists();

        if ($isExclusive) {
            $user->delete();

            return;
        }

        $user->removeRole($role);
    }

    /**
     * Users selectable in a "link to an existing account" control, with
     * their current roles so an admin can tell them apart (e.g. which one
     * is already the Admin login).
     *
     * @return array<int, array{id: int, name: string, email: string, roles: array<int, string>}>
     */
    public function linkableUsers(): array
    {
        return User::query()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->all(),
            ])
            ->all();
    }
}
