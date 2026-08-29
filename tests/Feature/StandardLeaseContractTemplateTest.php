<?php

use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Services\ContractDocumentService;
use App\Support\ContractTemplateVariables;
use App\Support\Money;
use App\Support\StandardLeaseContractTemplate;
use Database\Seeders\ContractTemplateSeeder;
use Illuminate\Support\Facades\Storage;

test('seeder publishes the standard lease template using every catalog variable', function () {
    $this->seed(ContractTemplateSeeder::class);

    $template = ContractTemplate::query()->where('name', StandardLeaseContractTemplate::NAME)->first();

    expect($template)->not->toBeNull();

    foreach (ContractTemplateVariables::keys() as $key) {
        expect($template->content)->toContain('data-template-variable="'.$key.'"');
    }
});

test('seeder is idempotent', function () {
    $this->seed(ContractTemplateSeeder::class);
    $this->seed(ContractTemplateSeeder::class);

    expect(ContractTemplate::query()->where('name', StandardLeaseContractTemplate::NAME)->count())->toBe(1);
});

test('standard lease template content is already in canonical sanitized form', function () {
    $raw = StandardLeaseContractTemplate::content();

    expect(ContractTemplateVariables::sanitizeHtml($raw))->toBe($raw);
});

test('standard lease template renders with no leftover placeholders', function () {
    Storage::fake('local');

    $this->seed(ContractTemplateSeeder::class);
    $template = ContractTemplate::query()->where('name', StandardLeaseContractTemplate::NAME)->firstOrFail();

    $ownerA = Owner::factory()->create([
        'name' => 'Joao Proprietario',
        'document' => '11111111111',
        'email' => 'joao.owner@example.com',
        'phone' => '5511988880000',
    ]);
    $property = Property::factory()->create([
        'name' => 'Apartamento 42',
        'address' => 'Rua das Acacias, 42 - Centro',
    ]);
    $property->owners()->attach($ownerA->id);

    $tenant = Tenant::factory()->create([
        'name' => 'Maria Inquilina',
        'document' => '52998224725',
        'email' => 'maria.tenant@example.com',
        'whatsapp' => '5511999990001',
    ]);
    $receiver = Receiver::factory()->create([
        'name' => 'Recebedor Pagamentos Ltda',
        'document' => '11222333000181',
    ]);

    $contract = Contract::factory()->active()->for($property)->for($tenant)->for($receiver)->create([
        'monthly_rent' => 1500.00,
        'due_day' => 5,
        'fine_rate' => 0.02,
        'monthly_interest_rate' => 0.01,
        'grace_days' => 3,
    ]);

    $service = app(ContractDocumentService::class);
    $contract = $service->generate($contract, $template);

    expect($contract->contract_text)
        ->toContain('Joao Proprietario')
        ->toContain('11111111111')
        ->toContain('joao.owner@example.com')
        ->toContain('5511988880000')
        ->toContain('Maria Inquilina')
        ->toContain('52998224725')
        ->toContain('maria.tenant@example.com')
        ->toContain('5511999990001')
        ->toContain('Apartamento 42')
        ->toContain('Rua das Acacias, 42 - Centro')
        ->toContain('Recebedor Pagamentos Ltda')
        ->toContain('11222333000181')
        ->toContain(Money::format(1500.00))
        ->toContain((string) $contract->starts_at->format('d/m/Y'))
        ->toContain((string) $contract->ends_at->format('d/m/Y'))
        ->toContain('data-template-variable="multa_percentual">2</span>%')
        ->toContain('data-template-variable="juros_percentual">1</span>%')
        ->and($contract->generated_document_path)->not->toBeNull();

    // Nenhum {{chave}} do catálogo pode sobreviver à renderização.
    foreach (ContractTemplateVariables::keys() as $key) {
        expect($contract->contract_text)->not->toContain('{{'.$key.'}}');
    }

    Storage::disk('local')->assertExists($contract->generated_document_path);
});
