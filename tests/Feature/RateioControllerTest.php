<?php

use App\Enums\RateioSplitMode;
use App\Models\Property;
use App\Models\Rateio;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('non admin cannot access the rateios page', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.rateios.index'))
        ->assertForbidden();
});

test('admin can create a rateio', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.rateios.store'), [
            'category' => 'agua',
            'reference' => 'Agosto/2026',
            'total_amount' => 300,
            'split_mode' => RateioSplitMode::Equal->value,
            'property_ids' => [$property->id],
        ])
        ->assertRedirect();

    expect(Rateio::query()->where('reference', 'Agosto/2026')->exists())->toBeTrue();
});

test('non admin cannot create a rateio', function () {
    $tenant = User::factory()->tenant()->create();
    $property = Property::factory()->create();

    $this->actingAs($tenant)
        ->post(route('admin.rateios.store'), [
            'category' => 'agua',
            'reference' => 'Agosto/2026',
            'total_amount' => 300,
            'split_mode' => RateioSplitMode::Equal->value,
            'property_ids' => [$property->id],
        ])
        ->assertForbidden();
});

test('admin can update a rateio', function () {
    $admin = User::factory()->admin()->create();
    $property = Property::factory()->create();
    $rateio = Rateio::factory()->create(['category' => 'agua', 'reference' => 'Agosto/2026', 'total_amount' => 100]);

    $this->actingAs($admin)
        ->put(route('admin.rateios.update', $rateio), [
            'category' => 'gas',
            'reference' => 'Agosto/2026',
            'total_amount' => 250,
            'split_mode' => RateioSplitMode::Equal->value,
            'property_ids' => [$property->id],
        ])
        ->assertRedirect();

    expect($rateio->fresh()->category)->toBe('gas')
        ->and((float) $rateio->fresh()->total_amount)->toBe(250.0);
});

test('admin can delete a rateio', function () {
    $admin = User::factory()->admin()->create();
    $rateio = Rateio::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.rateios.destroy', $rateio))
        ->assertRedirect();

    expect(Rateio::query()->find($rateio->id))->toBeNull();
});

test('non admin cannot delete a rateio', function () {
    $tenant = User::factory()->tenant()->create();
    $rateio = Rateio::factory()->create();

    $this->actingAs($tenant)
        ->delete(route('admin.rateios.destroy', $rateio))
        ->assertForbidden();
});

test('admin can download the invoice of a rateio', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $path = UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')->store('rateios', 'local');
    $rateio = Rateio::factory()->create([
        'invoice_path' => $path,
        'invoice_content_type' => 'application/pdf',
        'invoice_file_name' => 'nota.pdf',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.rateios.invoice', $rateio))
        ->assertSuccessful();
});

test('downloading the invoice 404s when the rateio has none', function () {
    $admin = User::factory()->admin()->create();
    $rateio = Rateio::factory()->create(['invoice_path' => null]);

    $this->actingAs($admin)
        ->get(route('admin.rateios.invoice', $rateio))
        ->assertNotFound();
});

test('non admin cannot download a rateio invoice', function () {
    Storage::fake('local');
    $tenant = User::factory()->tenant()->create();
    $path = UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')->store('rateios', 'local');
    $rateio = Rateio::factory()->create(['invoice_path' => $path]);

    $this->actingAs($tenant)
        ->get(route('admin.rateios.invoice', $rateio))
        ->assertForbidden();
});
