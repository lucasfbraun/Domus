<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amountPaid = fake()->randomFloat(2, 500, 5000);
        $fees = fake()->randomFloat(2, 5, 50);

        return [
            'charge_id' => Charge::factory(),
            'amount_paid' => $amountPaid,
            'net_amount' => $amountPaid - $fees,
            'fees' => $fees,
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'status' => fake()->randomElement(PaymentStatus::cases()),
            'paid_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'external_id' => fake()->optional()->uuid(),
        ];
    }
}
