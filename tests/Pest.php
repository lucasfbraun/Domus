<?php

use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        $this->withoutVite();
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Creates a Charge + an Approved Payment for it (a rent payment, as
 * opposed to a Deposit/Caução payment) — the unit IncomeReportTest builds
 * its fixtures out of, since almost every case there is "an approved rent
 * payment on this date/amount/receiver/contract".
 *
 * @param  array<string, mixed>  $chargeOverrides
 * @param  array<string, mixed>  $paymentOverrides
 */
function createApprovedRentPayment(array $chargeOverrides = [], array $paymentOverrides = []): Payment
{
    $charge = Charge::factory()->create($chargeOverrides);

    return Payment::factory()->for($charge)->create([
        'status' => PaymentStatus::Approved,
        'net_amount' => 950.00,
        'amount_paid' => 1000.00,
        'fees' => 50.00,
        ...$paymentOverrides,
    ]);
}

/**
 * Inertia responses round-trip through JSON, and PHP's json_encode drops
 * the ".0" off a whole-number float — so e.g. a 900.0 total comes back
 * from assertInertia() as an int 900, failing a strict
 * ->where('x', 900.0) comparison. Compare as float instead of guessing
 * the wire type.
 */
function amountEquals(float $expected): Closure
{
    return fn ($value) => (float) $value === $expected;
}

/**
 * Points the sqlite connection at a throwaway copy of the real (already
 * migrated) test database for the duration of the callback, then restores
 * the original connection — so tests can freely create/destroy/swap whole
 * database files (DatabaseBackupService) without touching the shared test
 * database other Feature tests rely on.
 *
 * @param  callable(string $scratchPath): void  $callback
 */
function withScratchSqliteDatabase(callable $callback): void
{
    $original = (string) config('database.connections.sqlite.database');
    $originalAbsolute = str_starts_with($original, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $original) === 1
        ? $original
        : base_path($original);

    $scratchPath = tempnam(sys_get_temp_dir(), 'domus-test-db-');
    File::copy($originalAbsolute, $scratchPath);

    config(['database.connections.sqlite.database' => $scratchPath]);
    DB::purge('sqlite');

    try {
        $callback($scratchPath);
    } finally {
        config(['database.connections.sqlite.database' => $original]);
        DB::purge('sqlite');
        DB::beginTransaction();
        File::delete($scratchPath);
    }
}
