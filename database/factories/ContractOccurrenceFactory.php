<?php

namespace Database\Factories;

use App\Enums\OccurrenceStatus;
use App\Models\Contract;
use App\Models\ContractOccurrence;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractOccurrence>
 */
class ContractOccurrenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'tenant_id' => Tenant::factory(),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(OccurrenceStatus::cases()),
            'resolved_at' => null,
            'resolution_note' => null,
        ];
    }
}
