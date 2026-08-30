# Database backup/restore only supports the sqlite driver

**Status**: accepted

`DatabaseBackupService` only implements backup/restore for the `sqlite` driver, even though `docker-compose.yml` also provisions MySQL and PostgreSQL containers. That's not an oversight: `.env.example` defaults to `DB_CONNECTION=sqlite`, `config/database.php` falls back to sqlite whenever no `DATABASE_URL`/`DB_URL` is set, and the production runtime image only has the `sqlite3` CLI installed — no `mysqldump`/`pg_dump`/`psql`. The MySQL/Postgres containers exist purely as optional Sail conveniences for local development, not because the app is deployed against them.

Building MySQL/Postgres dump-and-restore support now would mean shipping code that can't be exercised or verified against the actual runtime, and that fails silently (or loudly, mid-restore) the day someone actually needs it. `DatabaseBackupService::assertSupported()` throws a clear error on any other driver instead. If this project is ever deployed against MySQL/Postgres, that's the point to add real support — install the client binaries in the runtime image, add a per-driver dump/restore strategy (`spatie/db-dumper` is the natural choice, since `spatie/laravel-backup` itself is built on it), and test it against a real instance.

See also [ADR 0011](0011-database-driver-for-queue-cache-session.md): queue/cache/session all default to the same sqlite database (not Redis, despite it being provisioned) specifically so this backup/restore captures them too, instead of leaving them to drift out of sync with a restored snapshot.
