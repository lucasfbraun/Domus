<?php

namespace Database\Seeders;

use App\Enums\ChargeStatus;
use App\Enums\ContractStatus;
use App\Enums\PropertyStatus;
use App\Enums\SignatureStatus;
use App\Enums\TenantStatus;
use App\Models\Charge;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
            'email' => $tenantUser->email,
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
                'type' => 'apartment',
                'status' => PropertyStatus::Rented,
            ],
            [
                'name' => 'Casa Jardins',
                'address' => 'Av. Paulista, 500 - Jardins, São Paulo',
                'type' => 'house',
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
            'monthly_rent' => 2500.00,
            'due_day' => 10,
            'starts_at' => now()->subMonths(2)->startOfMonth(),
            'ends_at' => now()->addMonths(10)->endOfMonth(),
            'fine_rate' => 0.0200,
            'monthly_interest_rate' => 0.0100,
            'grace_days' => 3,
            'status' => ContractStatus::Active,
            'template_id' => $template->id,
            'contract_text' => $template->content,
            'signature_status' => SignatureStatus::Approved,
        ]);

        Charge::create([
            'contract_id' => $contract->id,
            'receiver_id' => $receiver->id,
            'reference' => now()->format('Y-m'),
            'due_date' => now()->addDays(10),
            'original_amount' => $contract->monthly_rent,
            'status' => ChargeStatus::Open,
        ]);

        Charge::create([
            'contract_id' => $contract->id,
            'receiver_id' => $receiver->id,
            'reference' => now()->addMonth()->format('Y-m'),
            'due_date' => now()->addMonth()->addDays(10),
            'original_amount' => $contract->monthly_rent,
            'status' => ChargeStatus::Open,
        ]);
    }
}
