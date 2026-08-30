<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Configure the factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if ($user->roles()->count() === 0) {
                Role::findOrCreate(UserRole::Admin->value);
                $user->assignRole(UserRole::Admin);
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => []);
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::findOrCreate(UserRole::Admin->value);
            $user->syncRoles([UserRole::Admin]);
        });
    }

    public function tenant(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::findOrCreate(UserRole::Tenant->value);
            $user->syncRoles([UserRole::Tenant]);
        });
    }

    public function receiver(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::findOrCreate(UserRole::Receiver->value);
            $user->syncRoles([UserRole::Receiver]);
        });
    }

    public function owner(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::findOrCreate(UserRole::Owner->value);
            $user->syncRoles([UserRole::Owner]);
        });
    }
}
