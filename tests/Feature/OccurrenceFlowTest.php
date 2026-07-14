<?php

use App\Enums\OccurrenceStatus;
use App\Mail\OccurrenceReportedMail;
use App\Mail\OccurrenceUpdatedMail;
use App\Models\Contract;
use App\Models\ContractOccurrence;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

test('tenant can create an occurrence and admins are emailed', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $tenantUser = User::factory()->tenant()->create();
    $tenant = Tenant::factory()->for($tenantUser, 'user')->create();
    $contract = Contract::factory()->active()->for($tenant)->create();

    $this->actingAs($tenantUser)
        ->post(route('occurrences.store'), [
            'contract_id' => $contract->id,
            'description' => 'Vazamento no banheiro',
            'photos' => [
                UploadedFile::fake()->image('foto.jpg'),
            ],
        ])
        ->assertRedirect();

    $occurrence = ContractOccurrence::query()->first();

    expect($occurrence)->not->toBeNull()
        ->and($occurrence->status)->toBe(OccurrenceStatus::Open)
        ->and($occurrence->photos)->toHaveCount(1)
        ->and($occurrence->tenant_id)->toBe($tenant->id);

    Mail::assertQueued(OccurrenceReportedMail::class);
    expect($admin->email)->toBe('admin@example.com');
});

test('admin can update occurrence status and tenant is emailed', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $tenantUser = User::factory()->tenant()->create(['email' => 'tenant@example.com']);
    $tenant = Tenant::factory()->for($tenantUser, 'user')->create(['email' => 'tenant@example.com']);
    $contract = Contract::factory()->active()->for($tenant)->create();
    $occurrence = ContractOccurrence::factory()->for($contract)->for($tenant)->create([
        'status' => OccurrenceStatus::Open,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.occurrences.update', $occurrence), [
            'status' => OccurrenceStatus::Resolved->value,
            'resolution_note' => 'Consertado',
        ])
        ->assertRedirect();

    expect($occurrence->fresh()->status)->toBe(OccurrenceStatus::Resolved)
        ->and($occurrence->fresh()->resolution_note)->toBe('Consertado');

    Mail::assertQueued(OccurrenceUpdatedMail::class);
});
