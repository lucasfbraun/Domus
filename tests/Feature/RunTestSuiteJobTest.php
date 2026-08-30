<?php

use App\Jobs\RunTestSuiteJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

test('a passing run is cached as passed with a parsed summary', function () {
    Process::fake([
        '*' => Process::result(output: "..........\n\n  Tests:    12 passed (30 assertions)\n  Duration: 4.20s\n", exitCode: 0),
    ]);
    Cache::put(RunTestSuiteJob::RUNNING_CACHE_KEY, true, now()->addMinutes(5));

    (new RunTestSuiteJob)->handle();

    $lastRun = Cache::get(RunTestSuiteJob::CACHE_KEY);

    expect($lastRun['status'])->toBe('passed')
        ->and($lastRun['exit_code'])->toBe(0)
        ->and($lastRun['summary'])->toBe('12 passed (30 assertions)')
        ->and(Cache::get(RunTestSuiteJob::RUNNING_CACHE_KEY))->toBeNull();
});

test('a failing run is cached as failed', function () {
    Process::fake([
        '*' => Process::result(output: "..F.......\n\n  Tests:    1 failed, 11 passed (30 assertions)\n", exitCode: 1),
    ]);
    Cache::put(RunTestSuiteJob::RUNNING_CACHE_KEY, true, now()->addMinutes(5));

    (new RunTestSuiteJob)->handle();

    $lastRun = Cache::get(RunTestSuiteJob::CACHE_KEY);

    expect($lastRun['status'])->toBe('failed')
        ->and($lastRun['exit_code'])->toBe(1)
        ->and($lastRun['summary'])->toBe('1 failed, 11 passed (30 assertions)')
        ->and(Cache::get(RunTestSuiteJob::RUNNING_CACHE_KEY))->toBeNull();
});

test('the running flag is cleared even if the process output has no parseable summary', function () {
    Process::fake([
        '*' => Process::result(output: 'algo deu muito errado antes de rodar qualquer teste', exitCode: 255),
    ]);
    Cache::put(RunTestSuiteJob::RUNNING_CACHE_KEY, true, now()->addMinutes(5));

    (new RunTestSuiteJob)->handle();

    $lastRun = Cache::get(RunTestSuiteJob::CACHE_KEY);

    expect($lastRun['status'])->toBe('failed')
        ->and($lastRun['summary'])->toBeNull()
        ->and(Cache::get(RunTestSuiteJob::RUNNING_CACHE_KEY))->toBeNull();
});

test('spawns the test process with an explicit testing environment, not whatever this job inherited', function () {
    // Regression test for a real bug: this job runs inside an already-booted
    // Laravel process (the queue worker), which has already loaded the real
    // local .env into its own $_ENV. A spawned child inherits that $_ENV at
    // startup; phpunit.xml's <env force="true"> only fixes getenv(), not
    // $_ENV, which is what Laravel's env() helper actually reads. Without
    // passing APP_ENV (and the rest) explicitly here, the spawned run
    // silently executes against local config instead of testing — every
    // write request then fails CSRF verification with a 419. See
    // docs/adr/0008-feature-checks-page.md.
    Process::fake([
        '*' => Process::result(output: "Tests:    1 passed (1 assertion)\n", exitCode: 0),
    ]);

    (new RunTestSuiteJob)->handle();

    Process::assertRan(function ($process) {
        $reflection = new ReflectionProperty($process, 'environment');
        $reflection->setAccessible(true);
        $environment = $reflection->getValue($process);

        return ($environment['APP_ENV'] ?? null) === 'testing'
            && ($environment['SESSION_DRIVER'] ?? null) === 'array'
            && ($environment['DB_DATABASE'] ?? null) === 'testing';
    });
});
