<?php

namespace Database\Factories;

use App\Enums\DepositStatus;
use App\Models\Contract;
use App\Models\Deposit;
use App\Models\Receiver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'receiver_id' => Receiver::factory(),
            'description' => 'Caução referente ao contrato de locação',
            'amount' => fake()->randomFloat(2, 500, 5000),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
            'status' => DepositStatus::Pending,
            'mercado_pago_order_id' => null,
            'mercado_pago_transaction_id' => null,
            'payment_url' => null,
            'pix_qr_code' => null,
            'pix_qr_code_base64' => null,
            'pix_expires_at' => null,
            'paid_at' => null,
            'refunded_at' => null,
            'refunded_amount' => null,
            'refund_note' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DepositStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
