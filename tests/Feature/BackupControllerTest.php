<?php

use App\Models\Owner;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('non admin cannot access the backups page', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('admin.backups.index'))
        ->assertForbidden();
});

test('admin sees the backups page with driver info and existing backups', function () {
    Storage::fake('local');
    Storage::disk('local')->put('backups/domus-backup-2024-01-01_000000_000000.sql.gz', 'x');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.backups.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/backups/Index')
            ->where('supported', true)
            ->where('driver', 'sqlite')
            ->has('backups', 1)
            ->where('backups.0.name', 'domus-backup-2024-01-01_000000_000000.sql.gz'));
});

test('admin can trigger a backup', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.backups.store'))
        ->assertRedirect();

    expect(app(DatabaseBackupService::class)->list())->toHaveCount(1);
});

test('non admin cannot trigger a backup', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->post(route('admin.backups.store'))
        ->assertForbidden();
});

test('admin can download an existing backup', function () {
    Storage::fake('local');
    Storage::disk('local')->put('backups/domus-backup-2024-01-01_000000_000000.sql.gz', 'conteudo');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.backups.download', 'domus-backup-2024-01-01_000000_000000.sql.gz'))
        ->assertSuccessful();
});

test('downloading a non-existent backup 404s', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.backups.download', 'domus-backup-2024-01-01_000000_000000.sql.gz'))
        ->assertNotFound();
});

test('restore requires typing the exact confirmation phrase', function () {
    Storage::fake('local');
    Storage::disk('local')->put('backups/domus-backup-2024-01-01_000000_000000.sql.gz', gzencode('SELECT 1;'));

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.backups.index'))
        ->post(route('admin.backups.restore', 'domus-backup-2024-01-01_000000_000000.sql.gz'), [
            'confirm' => 'eu confirmo',
        ])
        ->assertRedirect(route('admin.backups.index'))
        ->assertSessionHasErrors('confirm');
});

test('admin can restore a backup after typing the confirmation phrase', function () {
    Storage::fake('local');

    withScratchSqliteDatabase(function () {
        $admin = User::factory()->admin()->create();
        $backups = app(DatabaseBackupService::class);
        $baseline = $backups->create();

        Owner::create([
            'name' => 'Marcador HTTP',
            'document' => '1',
            'email' => 'marcador-http@example.com',
            'phone' => '1',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.backups.restore', $baseline), [
                'confirm' => 'RESTAURAR BANCO',
            ])
            ->assertRedirect();

        DB::purge('sqlite');

        expect(Owner::where('name', 'Marcador HTTP')->exists())->toBeFalse();
    });
});

test('non admin cannot import a backup', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->post(route('admin.backups.import'), [
            'file' => UploadedFile::fake()->createWithContent(
                'backup.sql.gz',
                (string) gzencode('CREATE TABLE t (id INTEGER);'),
            ),
        ])
        ->assertForbidden();
});

test('admin can import a valid backup file', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.backups.import'), [
            'file' => UploadedFile::fake()->createWithContent(
                'meu-backup-de-outro-servidor.sql.gz',
                (string) gzencode('CREATE TABLE imported_marker (id INTEGER);'),
            ),
        ])
        ->assertRedirect();

    expect(app(DatabaseBackupService::class)->list())->toHaveCount(1);
});

test('admin cannot import a file that is not a valid gzip', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.backups.index'))
        ->post(route('admin.backups.import'), [
            'file' => UploadedFile::fake()->createWithContent('nao-e-gzip.sql.gz', 'isto nao e gzip'),
        ])
        ->assertRedirect(route('admin.backups.index'))
        ->assertSessionHasErrors('file');

    expect(app(DatabaseBackupService::class)->list())->toHaveCount(0);
});

test('admin cannot import a file larger than the size limit', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from(route('admin.backups.index'))
        ->post(route('admin.backups.import'), [
            'file' => UploadedFile::fake()->create('grande.sql.gz', 102_401),
        ])
        ->assertRedirect(route('admin.backups.index'))
        ->assertSessionHasErrors('file');
});

test('admin can delete a backup', function () {
    Storage::fake('local');
    Storage::disk('local')->put('backups/domus-backup-2024-01-01_000000_000000.sql.gz', 'x');

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->delete(route('admin.backups.destroy', 'domus-backup-2024-01-01_000000_000000.sql.gz'))
        ->assertRedirect();

    Storage::disk('local')->assertMissing('backups/domus-backup-2024-01-01_000000_000000.sql.gz');
});
