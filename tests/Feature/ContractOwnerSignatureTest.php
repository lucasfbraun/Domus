<?php

use App\Models\Contract;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

test('admin cannot mark owner as signed without uploading a document first', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();

    $this->actingAs($admin)
        ->post(route('admin.contracts.owner-sign', $contract))
        ->assertSessionHasErrors('owner_signed_document');

    expect($contract->fresh()->owner_signed_at)->toBeNull();
});

test('admin can upload the owner-signed document and then mark it as signed', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();
    $file = UploadedFile::fake()->create('proprietario-assinado.pdf', 100, 'application/pdf');

    $this->actingAs($admin)
        ->post(route('admin.contracts.document.upload-owner-signed', $contract), [
            'owner_signed_document' => $file,
        ])
        ->assertRedirect();

    $contract->refresh();

    expect($contract->owner_signed_document_path)->not->toBeNull()
        ->and($contract->owner_signed_at)->toBeNull();

    Storage::disk('local')->assertExists($contract->owner_signed_document_path);

    $this->actingAs($admin)
        ->post(route('admin.contracts.owner-sign', $contract))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($contract->fresh()->owner_signed_at)->not->toBeNull();
});

test('owner-signed document download requires an uploaded file', function () {
    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->active()->create();

    $this->actingAs($admin)
        ->get(route('admin.contracts.document.owner-signed', $contract))
        ->assertNotFound();

    $file = UploadedFile::fake()->create('proprietario-assinado.pdf', 100, 'application/pdf');
    $this->actingAs($admin)
        ->post(route('admin.contracts.document.upload-owner-signed', $contract), [
            'owner_signed_document' => $file,
        ]);

    $this->actingAs($admin)
        ->get(route('admin.contracts.document.owner-signed', $contract->fresh()))
        ->assertSuccessful();
});
