<?php

namespace Database\Factories;

use App\Enums\ChargeStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\Receiver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Charge>
 */
class ChargeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'receiver_id' => Receiver::factory(),
            'reference' => fake()->unique()->regexify('[0-9]{4}-[0-9]{2}'),
            'due_date' => fake()->dateTimeBetween('now', '+2 months'),
            'original_amount' => fake()->randomFloat(2, 500, 5000),
            'status' => fake()->randomElement(ChargeStatus::cases()),
            'mercado_pago_order_id' => null,
            'mercado_pago_transaction_id' => null,
            'payment_url' => null,
            'pix_qr_code' => null,
            'pix_qr_code_base64' => null,
            'pix_expires_at' => null,
            'rateio_amount' => null,
            'last_reminder_event' => null,
            'last_reminder_sent_at' => null,
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ChargeStatus::Open,
            'due_date' => now()->addDays(fake()->numberBetween(5, 30)),
        ]);
    }
}
