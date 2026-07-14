<?php

namespace Database\Seeders;

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\SignatureStatus;
use App\Enums\TenantStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Owner;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Seed completo para teste manual (portal + admin + Pix sandbox).
     *
     * Pix real só é gerado quando:
     * - APP_ENV !== testing
     * - MP_ACCESS_TOKEN (ou token OAuth do recebedor) está configurado
     * - valor da cobrança (com juros/multa) ≤ R$ 1.000 (limite do sandbox Orders API)
     * - por isso o aluguel demo é R$ 900 (vencida com multa/juros fica ~R$ 927)
     *
     * Contas: admin@example.com / tenant@example.com / receiver@example.com
     * Senha: password
     *
     * Observação sandbox: o e-mail do Tenant (payer) usa @testuser.com;
     * o login do User permanece tenant@example.com.
     */
    public function run(MercadoPagoService $mercadoPago): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $tenantUser = User::factory()->tenant()->create([
            'name' => 'Tenant User',
            'email' => 'tenant@example.com',
            'password' => Hash::make('password'),
        ]);

        $receiverUser = User::factory()->receiver()->create([
            'name' => 'Receiver User',
            'email' => 'receiver@example.com',
            'password' => Hash::make('password'),
        ]);

        $tenant = Tenant::create([
            'user_id' => $tenantUser->id,
            'name' => $tenantUser->name,
            'document' => '52998224725',
            // Sandbox do Mercado Pago exige payer com @testuser.com (login do user permanece @example.com).
            'email' => 'tenant.demo@testuser.com',
            'whatsapp' => '5511999990001',
            'status' => TenantStatus::Active,
            'resident_count' => 2,
        ]);

        $receiver = Receiver::create([
            'user_id' => $receiverUser->id,
            'name' => $receiverUser->name,
            'document' => '11222333000181',
            'email' => $receiverUser->email,
            'mercado_pago_account' => 'receiver@example.com',
            'active' => true,
        ]);

        $owner = Owner::create([
            'name' => 'João Silva',
            'document' => '39053344705',
            'email' => 'joao.silva@example.com',
            'phone' => '5511988880000',
        ]);

        $properties = collect([
            [
                'name' => 'Apartamento Centro',
                'address' => 'Rua das Flores, 100 - Centro, São Paulo',
                'type' => PropertyType::Apartment,
                'status' => PropertyStatus::Rented,
            ],
            [
                'name' => 'Casa Jardins',
                'address' => 'Av. Paulista, 500 - Jardins, São Paulo',
                'type' => PropertyType::House,
                'status' => PropertyStatus::Available,
            ],
        ])->map(fn (array $data) => Property::create([
            ...$data,
            'owner_id' => $owner->id,
        ]));

        $template = ContractTemplate::create([
            'name' => 'Contrato de Locação Residencial',
            'content' => <<<'TEXT'
CONTRATO DE LOCAÇÃO RESIDENCIAL

Pelo presente instrumento, o LOCADOR e o LOCATÁRIO acordam as condições de locação do imóvel descrito neste contrato.

Cláusula 1 — Do objeto: O imóvel será utilizado exclusivamente para fins residenciais.
Cláusula 2 — Do prazo: A locação terá início e término conforme datas acordadas.
Cláusula 3 — Do aluguel: O valor mensal será pago até o dia acordado de cada mês.
TEXT,
        ]);

        $contract = Contract::create([
            'property_id' => $properties->first()->id,
            'tenant_id' => $tenant->id,
            'receiver_id' => $receiver->id,
            // Mantém aluguel + multa/juros ≤ R$ 1.000 para o sandbox Orders API gerar Pix.
            'monthly_rent' => 900.00,
            'due_day' => 10,
            'starts_at' => now()->subMonths(3)->startOfMonth(),
            'ends_at' => now()->addMonths(9)->endOfMonth(),
            'fine_rate' => 0.0200,
            'monthly_interest_rate' => 0.0100,
            'grace_days' => 3,
            'status' => ContractStatus::Active,
            'template_id' => $template->id,
            'contract_text' => $template->content,
            'signature_status' => SignatureStatus::Approved,
        ]);

        $paidCharge = Charge::create([
            'contract_id' => $contract->id,
            'receiver_id' => $receiver->id,
            'reference' => now()->subMonths(2)->format('Y-m'),
            'due_date' => now()->subMonths(2)->day(10),
            'original_amount' => $contract->monthly_rent,
            'status' => ChargeStatus::Paid,
        ]);

        Payment::create([
            'charge_id' => $paidCharge->id,
            'amount_paid' => $contract->monthly_rent,
            'net_amount' => round((float) $contract->monthly_rent * 0.99, 2),
            'fees' => round((float) $contract->monthly_rent * 0.01, 2),
            'method' => PaymentMethod::Pix,
            'status' => PaymentStatus::Approved,
            'paid_at' => now()->subMonths(2)->day(9),
            'external_id' => 'demo-paid-'.Str::uuid(),
        ]);

        Charge::create([
            'contract_id' => $contract->id,
            'receiver_id' => $receiver->id,
            'reference' => now()->subMonth()->format('Y-m'),
            'due_date' => now()->subMonth()->day(10),
            'original_amount' => $contract->monthly_rent,
            'status' => ChargeStatus::Overdue,
        ]);

        $currentCharge = Charge::create([
            'contract_id' => $contract->id,
            'receiver_id' => $receiver->id,
            'reference' => now()->format('Y-m'),
            'due_date' => now()->day(min(10, now()->daysInMonth)),
            'original_amount' => $contract->monthly_rent,
            'status' => ChargeStatus::Open,
        ]);

        Charge::create([
            'contract_id' => $contract->id,
            'receiver_id' => $receiver->id,
            'reference' => now()->addMonth()->format('Y-m'),
            'due_date' => now()->addMonth()->day(10),
            'original_amount' => $contract->monthly_rent,
            'status' => ChargeStatus::Open,
        ]);

        $this->generateSandboxPix($mercadoPago, $currentCharge);
    }

    private function generateSandboxPix(MercadoPagoService $mercadoPago, Charge $charge): void
    {
        if (app()->environment('testing')) {
            return;
        }

        if (! filled(config('services.mercadopago.access_token'))) {
            $this->command?->warn(
                'Pix sandbox não gerado: configure MP_ACCESS_TOKEN (credenciais de teste) e rode o seed de novo.',
            );

            return;
        }

        try {
            $result = $mercadoPago->createPixCharge($charge->fresh(['contract.tenant', 'receiver']));

            $this->command?->info('Pix sandbox gerado para cobrança #'.$charge->id.' ('.$charge->reference.').');
            $this->command?->info('Order: '.$result['orderId']);
            $this->command?->info(
                'QR/copia-e-cola disponível no portal do inquilino e em Admin → Cobranças.',
            );
        } catch (\Throwable $exception) {
            $this->command?->warn('Falha ao gerar Pix sandbox: '.$exception->getMessage());
            $this->command?->warn(
                'Dados locais foram criados. Gere o Pix manualmente em Admin → Cobranças ou no portal do inquilino.',
            );
        }
    }
}
