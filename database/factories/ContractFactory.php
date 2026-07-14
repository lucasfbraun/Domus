<?php

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Enums\SignatureStatus;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 year', 'now');
        $endsAt = (clone $startsAt)->modify('+'.fake()->numberBetween(12, 36).' months');

        return [
            'property_id' => Property::factory(),
            'tenant_id' => Tenant::factory(),
            'receiver_id' => Receiver::factory(),
            'monthly_rent' => fake()->randomFloat(2, 800, 5000),
            'due_day' => fake()->numberBetween(1, 28),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'fine_rate' => fake()->randomFloat(4, 0.01, 0.1),
            'monthly_interest_rate' => fake()->randomFloat(4, 0.005, 0.02),
            'grace_days' => fake()->numberBetween(0, 5),
            'status' => fake()->randomElement(ContractStatus::cases()),
            'template_id' => ContractTemplate::factory(),
            'contract_text' => fake()->optional()->paragraphs(3, true),
            'signature_status' => SignatureStatus::NotGenerated,
            'signed_document_path' => null,
            'signed_file_name' => null,
            'signed_uploaded_at' => null,
            'reviewed_at' => null,
            'review_note' => null,
            'generated_document_path' => null,
            'generated_document_updated_at' => null,
            'owner_signed_at' => null,
            'expiring_reminder_sent_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContractStatus::Active,
            'starts_at' => now()->subMonths(3),
            'ends_at' => now()->addMonths(9),
        ]);
    }
}
