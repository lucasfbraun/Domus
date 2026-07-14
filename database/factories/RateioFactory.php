<?php

namespace Database\Factories;

use App\Enums\RateioSplitMode;
use App\Models\Rateio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rateio>
 */
class RateioFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(['water', 'electricity', 'gas', 'condominium', 'outro']),
            'description' => fake()->optional()->sentence(),
            'reference' => fake()->unique()->regexify('[0-9]{4}-[0-9]{2}'),
            'total_amount' => fake()->randomFloat(2, 100, 2000),
            'invoice_path' => null,
            'invoice_content_type' => null,
            'invoice_file_name' => null,
            'split_mode' => fake()->randomElement(RateioSplitMode::cases()),
        ];
    }
}
