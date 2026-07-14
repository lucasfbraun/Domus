<?php

namespace Database\Factories;

use App\Models\Receiver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receiver>
 */
class ReceiverFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->receiver(),
            'name' => fake()->name(),
            'document' => '11222333000181',
            'email' => fake()->unique()->safeEmail(),
            'mercado_pago_account' => fake()->optional()->safeEmail(),
            'active' => true,
            'mp_user_id' => null,
            'mp_access_token' => null,
            'mp_refresh_token' => null,
            'mp_token_expires_at' => null,
            'mp_connected_at' => null,
            'mp_live_mode' => null,
        ];
    }

    public function connected(): static
    {
        return $this->state(fn (array $attributes) => [
            'mp_user_id' => (string) fake()->randomNumber(8),
            'mp_access_token' => fake()->sha256(),
            'mp_refresh_token' => fake()->sha256(),
            'mp_token_expires_at' => now()->addHours(6),
            'mp_connected_at' => now(),
            'mp_live_mode' => true,
        ]);
    }
}
