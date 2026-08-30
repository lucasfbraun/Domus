# "Funcionalidades" page runs the real test suite, local-dev only, via a background job

**Status**: accepted

The admin asked for a page listing the app's features with their test-coverage status, plus a button to actually run the tests and see them pass or fail — not a static report, a live run.

Three constraints shaped the design:

1. **Production has no dev dependencies.** The root `Dockerfile` runs `composer install --no-dev`, so Pest/PHPUnit simply aren't installed on a deployed instance. A "run tests" button there wouldn't have anything to execute.
2. **The full suite takes minutes** (around 3–4 minutes in this repo, and only grows). A synchronous HTTP request would hit the web server's timeout long before Pest finishes.
3. **Running arbitrary long-lived processes from a web request is exactly the kind of thing that shouldn't be exposed on a live, internet-facing instance**, even to an authenticated admin — it's an unusual capability for a property-management SaaS to expose at all, so it needs to default to off outside of a controlled environment.

That led to:

- **Gated by `config('services.feature_checks.enabled')`**, which defaults to `env('APP_ENV') === 'local'`, not a call to `app()->environment()` directly — the config-based check is what makes this testable via `config()` overrides without needing to fake the framework's environment detection, and is the reason the run action itself is safe to unit-test.
- **A second live check** that `vendor/bin/pest` actually exists on disk (`File::exists`), so even if someone enables the flag by mistake in an environment without dev dependencies, the button reports itself as unavailable instead of failing loudly mid-run.
- **Dispatched as a queued job** (`RunTestSuiteJob`), not run inline: the controller returns immediately, the job runs `php artisan test` with a 10-minute process timeout, and caches the result (pass/fail, exit code, parsed summary line, truncated output) for the page to poll via a small JSON status endpoint. This avoids any web server request-timeout entirely and keeps the admin UI responsive while it runs.
- **A single cached "is a run in progress" flag** prevents two concurrent runs from stepping on each other (the suite touches a shared sqlite test database), at the cost of a small non-atomic check-then-set race that's acceptable for a single-admin internal tool, not something used under real concurrency.

The feature catalog itself (`App\Support\FeatureCatalog`) is hand-maintained data, not derived from routes or auto-detected: knowing whether a test file's assertions actually exercise a given feature's behavior — versus merely touching the same model in unrelated setup — requires reading the test, not pattern-matching a filename. Test paths are still re-validated against the filesystem on every request, so a renamed or deleted test file falls back to "untested" instead of the catalog silently lying.

## A real bug this design surfaced: nested processes don't inherit a corrected environment the way you'd expect

Running the suite from `RunTestSuiteJob` — itself executing inside the already-booted, long-running queue worker process — is a genuinely different situation from running `php artisan test` from a fresh shell, and it broke in a way worth recording.

`phpunit.xml`'s `<env>` block (even with `force="true"` added) only calls PHP's `putenv()`, which fixes what `getenv()` returns in the child process but does **not** update the `$_ENV`/`$_SERVER` superglobals that child inherited at startup from its parent. The queue worker had already loaded the real `.env` (`APP_ENV=local`, a database session driver, etc.) into its own `$_ENV` long before the job ever ran. The spawned `php artisan test` child process inherits that populated `$_ENV` — and Laravel's `env()` helper (which `config/app.php` and everything downstream calls) reads `$_ENV`/`$_SERVER`, not `getenv()`. Net effect: the spawned run silently executed against the **real local config**, not the testing one — `app()->environment()` reported `local`, the real database-backed session driver was in play, and every test that wrote data (`POST`/`PUT`/`DELETE`) failed CSRF verification with a 419, because Laravel's CSRF middleware only auto-bypasses when `runningUnitTests()` is true, which itself depends on the same (wrongly-resolved) environment.

A plain `php artisan test` run from a fresh shell was never affected — there's no parent process's stale `$_ENV` to inherit. This only bites a process spawned as a *child* of an already-booted Laravel app, which is exactly what this feature does that nothing else in the codebase did before.

**Fix**: `RunTestSuiteJob::TESTING_ENV` passes the same key/value pairs from `phpunit.xml` explicitly via `Process::env(...)` when spawning the child. `Process::env()` builds the child's environment at the OS process-creation level (`proc_open`'s `$env` argument), which correctly populates the child's own `$_ENV`/`$_SERVER`/`getenv()` from scratch rather than trying to patch them after the fact. Keeping `force="true"` in `phpunit.xml` too is still worthwhile as defense-in-depth for any other nested-process path, but it alone does not fix this class of bug — the fix has to happen at process-spawn time, in the job itself. `RunTestSuiteJobTest` has a regression test asserting the exact environment passed to `Process::env()`, since this failure mode is invisible to any test that runs from a normal shell.

## A separate, pre-existing, still-unfixed limitation: occasional flaky failures on Windows/Docker/SQLite

After the environment fix above, a real end-to-end run of the full suite through this feature dropped from 108 false failures to 13 — all `QueryException`s (`disk I/O error`), none of them CSRF-related. Re-running each of those 13 in isolation immediately afterward, they all passed. This is a known, pre-existing characteristic of this project's dev setup, not something introduced by this feature: the `testing` sqlite database is a real file at the repo root (not `:memory:`, deliberately — `DatabaseBackupServiceTest`/`BackupControllerTest` need a real file path to shell out to the `sqlite3` CLI against, see `docs/adr/0005-sqlite-only-database-backup.md`), and Windows Docker Desktop's bind-mount filesystem driver doesn't reliably support SQLite's file locking under the write-heavy, rapid-succession access pattern a full test run produces — occasionally producing a transient "disk I/O error" on an otherwise-correct test.

This means the "Funcionalidades" page can occasionally report a failure that has nothing to do with the actual code. It's the same class of flakiness a manual `php artisan test` run already exhibits on this stack; this feature doesn't make it worse or better, it just makes it visible in a new place. Not fixed here — moving the testing database off a real file would break the backup tests' whole reason for existing, and that trade-off predates this feature. If a run reports a failure, the admin's next move should be re-running just the failing test(s) before treating it as a real regression.
