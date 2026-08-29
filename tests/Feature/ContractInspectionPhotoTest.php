<?php

use App\Models\Contract;
use App\Models\ContractInspectionPhoto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can upload an inspection photo', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.contracts.inspection-photos.store', $contract), [
            'photo' => UploadedFile::fake()->image('sala.jpg'),
            'room' => 'Sala',
            'caption' => 'Antes da entrega',
        ])
        ->assertRedirect();

    $photo = ContractInspectionPhoto::query()->where('contract_id', $contract->id)->first();

    expect($photo)->not->toBeNull()
        ->and($photo->room)->toBe('Sala')
        ->and($photo->caption)->toBe('Antes da entrega');

    Storage::disk('local')->assertExists($photo->storage_path);
});

test('admin can view an uploaded inspection photo inline', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->create();
    $path = UploadedFile::fake()->image('quarto.jpg')->store("contracts/{$contract->id}/inspection", 'local');

    $photo = ContractInspectionPhoto::factory()->for($contract)->create([
        'storage_path' => $path,
        'file_name' => 'quarto.jpg',
        'content_type' => 'image/jpeg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.contracts.inspection-photos.show', [$contract, $photo]))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'image/jpeg');
});

test('viewing a photo from a different contract returns 404', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->create();
    $otherContract = Contract::factory()->create();
    $photo = ContractInspectionPhoto::factory()->for($otherContract)->create();

    $this->actingAs($admin)
        ->get(route('admin.contracts.inspection-photos.show', [$contract, $photo]))
        ->assertNotFound();
});

test('non admin cannot view inspection photos', function () {
    $tenant = User::factory()->tenant()->create();
    $contract = Contract::factory()->create();
    $photo = ContractInspectionPhoto::factory()->for($contract)->create();

    $this->actingAs($tenant)
        ->get(route('admin.contracts.inspection-photos.show', [$contract, $photo]))
        ->assertForbidden();
});

test('admin can remove an inspection photo', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $contract = Contract::factory()->create();
    $path = UploadedFile::fake()->image('foto.jpg')->store("contracts/{$contract->id}/inspection", 'local');
    $photo = ContractInspectionPhoto::factory()->for($contract)->create(['storage_path' => $path]);

    $this->actingAs($admin)
        ->delete(route('admin.contracts.inspection-photos.destroy', [$contract, $photo]))
        ->assertRedirect();

    expect(ContractInspectionPhoto::query()->find($photo->id))->toBeNull();
    Storage::disk('local')->assertMissing($path);
});
