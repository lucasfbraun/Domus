<?php

use App\Enums\ContractStatus;
use App\Enums\SignatureStatus;
use App\Mail\ContractDocumentReviewedMail;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Receiver;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ContractDocumentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

test('admin can create a contract with financial fields and witnesses', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();
    $tenant = Tenant::factory()->create();
    $receiver = Receiver::factory()->create();
    $witness = Receiver::factory()->create();
    $template = ContractTemplate::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.contracts.store'), [
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'receiver_id' => $receiver->id,
            'template_id' => $template->id,
            'monthly_rent' => 1500,
            'due_day' => 10,
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
            'fine_percent' => 2,
            'interest_percent' => 1,
            'grace_days' => 3,
            'status' => ContractStatus::Active->value,
            'witness_receiver_ids' => [$witness->id],
        ])
        ->assertRedirect(route('admin.contracts.index'));

    $contract = Contract::query()->first();

    expect($contract)->not->toBeNull()
        ->and((float) $contract->monthly_rent)->toBe(1500.0)
        ->and((float) $contract->fine_rate)->toBe(0.02)
        ->and((float) $contract->monthly_interest_rate)->toBe(0.01)
        ->and($contract->witnesses)->toHaveCount(1);
});

test('admin can generate document and review signed upload with email', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create([
        'signature_status' => SignatureStatus::NotGenerated,
    ]);
    $template = ContractTemplate::factory()->create([
        'content' => 'Contrato para {{inquilino_nome}} no imovel {{imovel_nome}}',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.contracts.document.generate', $contract), [
            'template_id' => $template->id,
        ])
        ->assertRedirect();

    $contract->refresh();

    expect($contract->signature_status)->toBe(SignatureStatus::AwaitingSignature)
        ->and($contract->generated_document_path)->not->toBeNull();

    $file = UploadedFile::fake()->create('contrato-assinado.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('contracts.document.upload-signed', $contract), [
            'signed_document' => $file,
        ])
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.contracts.document.review', $contract), [
            'action' => 'approve',
            'review_note' => 'Ok',
        ])
        ->assertRedirect();

    expect($contract->fresh()->signature_status)->toBe(SignatureStatus::Approved);

    Mail::assertQueued(ContractDocumentReviewedMail::class);
});

test('generated document joins data from every owner of the property', function () {
    $property = Property::factory()->create();
    $ownerA = Owner::factory()->create([
        'name' => 'Joao Silva',
        'document' => '11111111111',
        'email' => 'joao@example.com',
        'phone' => '5511999990001',
    ]);
    $ownerB = Owner::factory()->create([
        'name' => 'Maria Silva',
        'document' => '22222222222',
        'email' => 'maria@example.com',
        'phone' => '5511999990002',
    ]);
    $property->owners()->attach([$ownerA->id, $ownerB->id]);

    $contract = Contract::factory()->active()->for($property)->create();
    $template = ContractTemplate::factory()->create([
        'content' => 'Proprietarios: {{proprietario_nome}} | Docs: {{proprietario_documento}} | E-mails: {{proprietario_email}} | Telefones: {{proprietario_telefone}}',
    ]);

    $service = app(ContractDocumentService::class);
    $contract = $service->generate($contract, $template);

    expect($contract->contract_text)
        ->toContain('Joao Silva, Maria Silva')
        ->toContain('11111111111, 22222222222')
        ->toContain('joao@example.com, maria@example.com')
        ->toContain('5511999990001, 5511999990002');
});

test('generated document leaves owner variables empty when property has no owner', function () {
    $property = Property::factory()->create();
    $contract = Contract::factory()->active()->for($property)->create();
    $template = ContractTemplate::factory()->create([
        'content' => 'Proprietario: [{{proprietario_nome}}]',
    ]);

    $service = app(ContractDocumentService::class);
    $contract = $service->generate($contract, $template);

    expect($contract->contract_text)->toContain('Proprietario: []');
});
