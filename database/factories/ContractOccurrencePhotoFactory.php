<?php

namespace Database\Factories;

use App\Models\ContractOccurrence;
use App\Models\ContractOccurrencePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractOccurrencePhoto>
 */
class ContractOccurrencePhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->uuid().'.jpg';

        return [
            'occurrence_id' => ContractOccurrence::factory(),
            'storage_path' => 'occurrences/'.$fileName,
            'file_name' => $fileName,
            'content_type' => 'image/jpeg',
        ];
    }
}
