<?php

use App\Jobs\CreateDatabaseBackupJob;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Storage;

test('the job creates a database backup', function () {
    Storage::fake('local');

    (new CreateDatabaseBackupJob)->handle(app(DatabaseBackupService::class));

    expect(app(DatabaseBackupService::class)->list())->toHaveCount(1);
});
