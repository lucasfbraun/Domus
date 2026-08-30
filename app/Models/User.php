<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $must_change_password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * @return HasOne<Tenant, $this>
     */
    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * @return HasOne<Receiver, $this>
     */
    public function receiver(): HasOne
    {
        return $this->hasOne(Receiver::class);
    }

    /**
     * Plural, unlike tenant()/receiver(): a shared login can be linked from
     * more than one Owner record (e.g. co-owned properties), so there's no
     * single canonical "the" owner for a User.
     *
     * @return HasMany<Owner, $this>
     */
    public function owners(): HasMany
    {
        return $this->hasMany(Owner::class);
    }

    /**
     * Picks which portal a user lands on right after login when they hold
     * more than one role. Admin wins first — it's the most capable role, and
     * anyone who also holds Owner/Receiver/Tenant can still reach that
     * portal from the sidebar (see AppSidebar.vue's portal nav items).
     */
    public function homeRouteName(): string
    {
        return match (true) {
            $this->hasRole(UserRole::Admin) => UserRole::Admin->homeRoute(),
            $this->hasRole(UserRole::Owner) => UserRole::Owner->homeRoute(),
            $this->hasRole(UserRole::Receiver) => UserRole::Receiver->homeRoute(),
            $this->hasRole(UserRole::Tenant) => UserRole::Tenant->homeRoute(),
            default => UserRole::Admin->homeRoute(),
        };
    }
}
