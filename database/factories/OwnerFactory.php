<?php

namespace Database\Factories;

use App\Models\Owner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Owner>
 */
class OwnerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'document' => '52998224725',
            'email' => fake()->unique()->safeEmail(),
            'phone' => '5511'.fake()->numerify('9########'),
        ];
    }
}
