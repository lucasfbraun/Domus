<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->tenant(),
            'name' => fake()->name(),
            'document' => '52998224725',
            'email' => fake()->unique()->safeEmail(),
            'whatsapp' => '5511'.fake()->numerify('9########'),
            'status' => fake()->randomElement(TenantStatus::cases()),
            'resident_count' => fake()->numberBetween(1, 5),
        ];
    }
}
