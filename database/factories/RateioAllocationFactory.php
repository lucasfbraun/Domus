<?php

namespace Database\Factories;

use App\Models\Charge;
use App\Models\Property;
use App\Models\Rateio;
use App\Models\RateioAllocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RateioAllocation>
 */
class RateioAllocationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rateio_id' => Rateio::factory(),
            'property_id' => Property::factory(),
            'amount' => fake()->randomFloat(2, 50, 500),
            'charge_id' => null,
            'applied_at' => null,
        ];
    }
}
