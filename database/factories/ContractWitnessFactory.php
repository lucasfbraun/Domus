<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractWitness;
use App\Models\Receiver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractWitness>
 */
class ContractWitnessFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'receiver_id' => Receiver::factory(),
            'signed_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
