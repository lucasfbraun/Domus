<?php

namespace App\Jobs;

use App\Http\Controllers\Admin\FeatureCheckController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

/**
 * Runs the real Pest/PHPUnit suite (`php artisan test`) in the background
 * and caches the result for the "Funcionalidades" admin page to poll.
 *
 * Only ever dispatched when {@see FeatureCheckController}
 * has confirmed the runner is available (local dev, dev dependencies
 * installed) — see that controller's docblock for why this can't run in
 * production or synchronously within a request.
 */
class RunTestSuiteJob implements ShouldQueue
{
    use Queueable;

    public const CACHE_KEY = 'feature-checks:last-run';

    public const RUNNING_CACHE_KEY = 'feature-checks:running';

    private const MAX_STORED_OUTPUT_CHARS = 20_000;

    public int $timeout = 600;

    /**
     * Must mirror phpunit.xml's <php><env> block. Belt-and-suspenders with
     * that file's `force="true"` attributes: PHPUnit's env forcing only
     * calls putenv(), which fixes getenv() but not the $_ENV/$_SERVER
     * superglobals a child PHP process inherits at startup from this job's
     * own already-booted (queue worker) process — and Laravel's env()
     * helper reads those superglobals, not getenv(). Without passing these
     * explicitly to Process::env() here, the spawned test run silently
     * inherits this job's real local config (APP_ENV=local, a real session
     * driver, etc.) instead of the testing one, and everything that writes
     * fails CSRF verification with a 419. See
     * docs/adr/0008-feature-checks-page.md.
     */
    private const TESTING_ENV = [
        'APP_ENV' => 'testing',
        'APP_MAINTENANCE_DRIVER' => 'file',
        'BCRYPT_ROUNDS' => '4',
        'BROADCAST_CONNECTION' => 'null',
        'CACHE_STORE' => 'array',
        'DB_DATABASE' => 'testing',
        'DB_URL' => '',
        'MAIL_MAILER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'SESSION_DRIVER' => 'array',
        'PULSE_ENABLED' => 'false',
        'TELESCOPE_ENABLED' => 'false',
        'NIGHTWATCH_ENABLED' => 'false',
    ];

    public function handle(): void
    {
        $startedAt = now();

        try {
            $result = Process::path(base_path())
                ->env(self::TESTING_ENV)
                ->timeout(590)
                ->run(['php', 'artisan', 'test', '--colors=never']);

            $output = $result->output().$result->errorOutput();

            Cache::put(self::CACHE_KEY, [
                'status' => $result->successful() ? 'passed' : 'failed',
                'exit_code' => $result->exitCode(),
                'summary' => $this->extractSummary($output),
                'output' => $this->truncate($output),
                'started_at' => $startedAt->toIso8601String(),
                'finished_at' => now()->toIso8601String(),
                'duration_seconds' => now()->getTimestamp() - $startedAt->getTimestamp(),
            ], now()->addDay());
        } finally {
            Cache::forget(self::RUNNING_CACHE_KEY);
        }
    }

    private function extractSummary(string $output): ?string
    {
        return preg_match('/Tests:\s+(.+)/', $output, $matches) === 1
            ? trim($matches[1])
            : null;
    }

    private function truncate(string $output): string
    {
        return mb_strlen($output) > self::MAX_STORED_OUTPUT_CHARS
            ? "... (saida truncada, mostrando o final) ...\n".mb_substr($output, -self::MAX_STORED_OUTPUT_CHARS)
            : $output;
    }
}
