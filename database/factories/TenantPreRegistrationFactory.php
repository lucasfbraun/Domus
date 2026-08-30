<?php

namespace Database\Factories;

use App\Enums\PreRegistrationStatus;
use App\Models\TenantPreRegistration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TenantPreRegistration>
 */
class TenantPreRegistrationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => Str::random(40),
            'status' => PreRegistrationStatus::Pending,
            'name' => null,
            'document' => null,
            'email' => null,
            'whatsapp' => null,
            'resident_count' => null,
            'invited_at' => now(),
            'expires_at' => now()->addDays(7),
            'submitted_at' => null,
            'reviewed_at' => null,
            'reviewed_by' => null,
            'rejection_note' => null,
            'tenant_id' => null,
        ];
    }

    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PreRegistrationStatus::InReview,
            'name' => fake()->name(),
            'document' => '52998224725',
            'email' => fake()->unique()->safeEmail(),
            'whatsapp' => '5511'.fake()->numerify('9########'),
            'resident_count' => fake()->numberBetween(1, 5),
            'submitted_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'invited_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
        ]);
    }
}
