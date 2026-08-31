<?php

use App\Models\Owner;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

const BACKUP_FILENAME_PATTERN = '/^domus-backup-\d{4}-\d{2}-\d{2}_\d{6}_\d{6}\.sql\.gz$/';

test('create dumps the live database schema into a gzipped file on the local disk', function () {
    Storage::fake('local');

    $filename = app(DatabaseBackupService::class)->create();

    expect($filename)->toMatch(BACKUP_FILENAME_PATTERN);
    Storage::disk('local')->assertExists('backups/'.$filename);

    $sql = gzdecode((string) Storage::disk('local')->get('backups/'.$filename));

    expect($sql)
        ->toBeString()
        ->toContain('CREATE TABLE')
        ->toContain('"contracts"');
});

test('list returns stored backups newest first', function () {
    Storage::fake('local');

    $service = app(DatabaseBackupService::class);

    Storage::disk('local')->put('backups/domus-backup-2020-01-01_000000_000000.sql.gz', 'old');
    Storage::disk('local')->put('backups/domus-backup-2024-06-15_120000_500000.sql.gz', 'new');
    Storage::disk('local')->put('backups/not-a-backup.txt', 'ignore me');

    $list = $service->list();

    expect($list)->toHaveCount(2)
        ->and($list[0]['name'])->toBe('domus-backup-2024-06-15_120000_500000.sql.gz')
        ->and($list[1]['name'])->toBe('domus-backup-2020-01-01_000000_000000.sql.gz');
});

test('restore rejects a filename outside the expected pattern', function () {
    Storage::fake('local');

    app(DatabaseBackupService::class)->restore('../../etc/passwd');
})->throws(InvalidArgumentException::class);

test('restore rejects a backup that does not exist on disk', function () {
    Storage::fake('local');

    app(DatabaseBackupService::class)->restore('domus-backup-2020-01-01_000000_000000.sql.gz');
})->throws(RuntimeException::class);

test('restore replaces the live database and creates an undoable safety backup first', function () {
    Storage::fake('local');

    withScratchSqliteDatabase(function () {
        $service = app(DatabaseBackupService::class);

        $baseline = $service->create();

        Owner::create([
            'name' => 'Marcador de teste',
            'document' => '1',
            'email' => 'marcador@example.com',
            'phone' => '1',
        ]);

        expect(Owner::count())->toBe(1);

        $safetyBackup = $service->restore($baseline);
        DB::purge('sqlite');

        expect($safetyBackup)->toMatch(BACKUP_FILENAME_PATTERN)
            ->and($safetyBackup)->not->toBe($baseline)
            ->and(Owner::count())->toBe(0);

        // The safety backup captured the pre-restore state, so restoring it
        // undoes the restore we just did.
        $service->restore($safetyBackup);
        DB::purge('sqlite');

        expect(Owner::where('name', 'Marcador de teste')->exists())->toBeTrue();
    });
});

test('restore refuses a backup file that is not a valid gzip', function () {
    Storage::fake('local');
    Storage::disk('local')->put('backups/domus-backup-2020-01-01_000000_000000.sql.gz', 'not gzip at all');

    app(DatabaseBackupService::class)->restore('domus-backup-2020-01-01_000000_000000.sql.gz');
})->throws(RuntimeException::class);

test('prune deletes the oldest backups beyond the keep count', function () {
    Storage::fake('local');
    $service = app(DatabaseBackupService::class);

    Storage::disk('local')->put('backups/domus-backup-2020-01-01_000000_000000.sql.gz', 'a');
    Storage::disk('local')->put('backups/domus-backup-2020-01-02_000000_000000.sql.gz', 'b');
    Storage::disk('local')->put('backups/domus-backup-2020-01-03_000000_000000.sql.gz', 'c');

    $deleted = $service->prune(2);

    expect($deleted)->toBe(['domus-backup-2020-01-01_000000_000000.sql.gz']);

    $remaining = collect($service->list())->pluck('name');
    expect($remaining)->toHaveCount(2)
        ->and($remaining)->toContain('domus-backup-2020-01-03_000000_000000.sql.gz')
        ->and($remaining)->toContain('domus-backup-2020-01-02_000000_000000.sql.gz');
});

test('prune does nothing when the backup count is already within the limit', function () {
    Storage::fake('local');
    $service = app(DatabaseBackupService::class);

    Storage::disk('local')->put('backups/domus-backup-2020-01-01_000000_000000.sql.gz', 'a');

    expect($service->prune(5))->toBe([]);
    expect($service->list())->toHaveCount(1);
});
