<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Exports/imports the whole application database as a single file, for
 * manual disaster-recovery backups (Admin -> Backups).
 *
 * Only the `sqlite` driver is implemented — that's the only one this app
 * actually runs on (see docs/adr/0005-sqlite-only-database-backup.md).
 * Backups are plain gzipped `sqlite3 .dump` SQL text, stored privately on
 * the `local` disk under `backups/`, never on a publicly served disk.
 */
class DatabaseBackupService
{
    private const DISK = 'local';

    private const DIRECTORY = 'backups';

    private const FILENAME_PATTERN = '/^domus-backup-(\d{4}-\d{2}-\d{2}_\d{6}_\d{6})\.sql\.gz$/';

    private const TIMESTAMP_FORMAT = 'Y-m-d_His_u';

    public function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    public function isSupported(): bool
    {
        return $this->driver() === 'sqlite';
    }

    /**
     * @return array<int, array{name: string, size: int, created_at: Carbon}>
     */
    public function list(): array
    {
        $disk = Storage::disk(self::DISK);

        if (! $disk->exists(self::DIRECTORY)) {
            return [];
        }

        return collect($disk->files(self::DIRECTORY))
            ->map(fn (string $path) => basename($path))
            ->map(function (string $name) use ($disk) {
                if (preg_match(self::FILENAME_PATTERN, $name, $matches) !== 1) {
                    return null;
                }

                return [
                    'name' => $name,
                    'size' => $disk->size(self::DIRECTORY.'/'.$name),
                    // Parsed from the filename, not filesystem mtime — mtime
                    // isn't preserved across copies/deploys and two backups
                    // taken close together could tie at second-resolution.
                    'created_at' => Carbon::createFromFormat(self::TIMESTAMP_FORMAT, $matches[1]),
                ];
            })
            ->filter()
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    /**
     * Dumps the live database to a timestamped, gzipped SQL file and stores
     * it. Returns the stored filename.
     */
    public function create(): string
    {
        $this->assertSupported();

        $sql = $this->dumpCurrentDatabase();
        $filename = 'domus-backup-'.now()->format(self::TIMESTAMP_FORMAT).'.sql.gz';

        Storage::disk(self::DISK)->put(
            self::DIRECTORY.'/'.$filename,
            (string) gzencode($sql, 9),
        );

        return $filename;
    }

    /**
     * Replaces the live database with the contents of a stored backup.
     *
     * A fresh safety backup of the *current* state is taken first, so a
     * restore is itself always undoable — its filename is returned so the
     * caller can surface it ("if this goes wrong, restore {safety} instead").
     */
    public function restore(string $filename): string
    {
        $this->assertSupported();
        $this->assertValidFilename($filename);

        $disk = Storage::disk(self::DISK);
        $path = self::DIRECTORY.'/'.$filename;

        if (! $disk->exists($path)) {
            throw new RuntimeException("Backup [{$filename}] nao encontrado.");
        }

        // gzdecode() both returns false AND raises an E_WARNING on invalid
        // input; the warning alone would otherwise be promoted to an
        // ErrorException before the false-check below ever runs.
        $sql = @gzdecode((string) $disk->get($path));

        if ($sql === false) {
            throw new RuntimeException("Backup [{$filename}] esta corrompido ou nao e um gzip valido.");
        }

        $safetyBackup = $this->create();

        $newDatabasePath = $this->buildDatabaseFile($sql);
        $this->swapLiveDatabase($newDatabasePath);

        return $safetyBackup;
    }

    /**
     * Stores an externally-produced backup file (e.g. downloaded from another
     * environment) as if it had been generated here, so it shows up in
     * {@see list()} and can be restored/downloaded/deleted like any other.
     *
     * Never touches the live database: content is validated by decompressing
     * it and rebuilding it into a standalone sqlite file first (the same
     * check {@see restore()} relies on), and the upload is rejected if that
     * fails. The stored filename is always freshly generated here — the
     * uploaded file's own name is discarded — so it always matches
     * FILENAME_PATTERN regardless of what the file was called before.
     */
    public function import(UploadedFile $file): string
    {
        $this->assertSupported();

        $compressed = (string) File::get($file->getRealPath());

        // gzdecode() both returns false AND raises an E_WARNING on invalid
        // input; the warning alone would otherwise be promoted to an
        // ErrorException before the false-check below ever runs.
        $sql = @gzdecode($compressed);

        if ($sql === false) {
            throw new RuntimeException('O arquivo enviado nao e um backup .sql.gz valido.');
        }

        $tempPath = $this->buildDatabaseFile($sql);
        File::delete($tempPath);

        $filename = 'domus-backup-'.now()->format(self::TIMESTAMP_FORMAT).'.sql.gz';
        Storage::disk(self::DISK)->put(self::DIRECTORY.'/'.$filename, $compressed);

        return $filename;
    }

    public function download(string $filename): string
    {
        $this->assertValidFilename($filename);

        $path = self::DIRECTORY.'/'.$filename;

        if (! Storage::disk(self::DISK)->exists($path)) {
            throw new RuntimeException("Backup [{$filename}] nao encontrado.");
        }

        return Storage::disk(self::DISK)->path($path);
    }

    public function delete(string $filename): void
    {
        $this->assertValidFilename($filename);

        Storage::disk(self::DISK)->delete(self::DIRECTORY.'/'.$filename);
    }

    /**
     * Runs `sqlite3 <db> .dump` against the live database and returns the
     * SQL text. A dump is a consistent point-in-time snapshot even with a
     * concurrent writer, because sqlite3 takes its own read transaction —
     * unlike copying the raw file, it can't capture a half-written page.
     */
    private function dumpCurrentDatabase(): string
    {
        $livePath = $this->livePath();

        if (! File::exists($livePath)) {
            throw new RuntimeException("Arquivo do banco [{$livePath}] nao encontrado.");
        }

        $result = Process::timeout(120)->run(['sqlite3', $livePath, '.dump']);

        if ($result->failed()) {
            throw new RuntimeException('Falha ao gerar dump do banco: '.$result->errorOutput());
        }

        return $result->output();
    }

    /**
     * Builds a brand-new, standalone sqlite database file from raw SQL text
     * and returns its path. Pure and side-effect-free on the live database —
     * this is what makes restore safe to validate before committing to it.
     */
    private function buildDatabaseFile(string $sql): string
    {
        $newPath = tempnam(sys_get_temp_dir(), 'domus-restore-');

        if ($newPath === false) {
            throw new RuntimeException('Nao foi possivel criar arquivo temporario para restauracao.');
        }

        File::delete($newPath);

        $result = Process::timeout(120)->input($sql)->run(['sqlite3', $newPath]);

        if ($result->failed() || ! File::exists($newPath)) {
            throw new RuntimeException('Falha ao reconstruir banco a partir do backup: '.$result->errorOutput());
        }

        $this->assertOpenable($newPath);

        return $newPath;
    }

    /**
     * Sanity-checks that a sqlite file is actually a valid, queryable
     * database before it's trusted enough to replace the live one.
     */
    private function assertOpenable(string $path): void
    {
        try {
            $pdo = new \PDO('sqlite:'.$path);
            $pdo->query('SELECT count(*) FROM sqlite_master');
        } catch (\PDOException $exception) {
            File::delete($path);

            throw new RuntimeException('Backup reconstruido nao e um banco sqlite valido: '.$exception->getMessage());
        } finally {
            $pdo = null;
        }
    }

    private function swapLiveDatabase(string $newDatabasePath): void
    {
        $livePath = $this->livePath();

        DB::disconnect();

        File::move($newDatabasePath, $livePath);

        DB::reconnect();
    }

    private function livePath(): string
    {
        $configured = (string) DB::connection()->getDatabaseName();

        return $this->isAbsolutePath($configured) ? $configured : base_path($configured);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1;
    }

    private function assertSupported(): void
    {
        if (! $this->isSupported()) {
            throw new RuntimeException(
                "Backup/restauracao so e suportado com o driver sqlite (atual: {$this->driver()}).",
            );
        }
    }

    private function assertValidFilename(string $filename): void
    {
        if (preg_match(self::FILENAME_PATTERN, $filename) !== 1) {
            throw new \InvalidArgumentException('Nome de arquivo de backup invalido.');
        }
    }
}
