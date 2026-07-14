<?php

namespace Database\Factories;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Owner;
use App\Models\Property;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Property>
 */
class PropertyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'type' => fake()->randomElement(PropertyType::cases()),
            'status' => fake()->randomElement(PropertyStatus::cases()),
            'owner_id' => Owner::factory(),
        ];
    }
}
