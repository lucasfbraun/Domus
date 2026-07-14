<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractInspectionPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractInspectionPhoto>
 */
class ContractInspectionPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->uuid().'.jpg';

        return [
            'contract_id' => Contract::factory(),
            'storage_path' => 'inspections/'.$fileName,
            'file_name' => $fileName,
            'content_type' => 'image/jpeg',
            'caption' => fake()->optional()->sentence(),
            'room' => fake()->optional()->randomElement(['living_room', 'kitchen', 'bedroom', 'bathroom']),
            'position' => fake()->numberBetween(0, 10),
        ];
    }
}
