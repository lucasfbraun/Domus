# Queue, cache, and session all default to the `database` driver, not Redis

**Status**: accepted

`docker-compose.yml` provisions a Redis container and `.env.example` ships full `REDIS_*` settings, but `QUEUE_CONNECTION`, `CACHE_STORE`, and `SESSION_DRIVER` all default to `database` — Redis is available, not used by default.

This follows directly from [ADR 0005](0005-sqlite-only-database-backup.md): the app runs on sqlite, and `DatabaseBackupService` backs up and restores that one file via `sqlite3 <path> .dump`. Putting jobs/cache/sessions in the same sqlite database means:

- A single backup captures **everything** — pending queued jobs, cached values, and every logged-in session, alongside the actual business data — with no second system to separately back up or reason about.
- A restore is equally complete: restoring an older backup also rolls back in-flight jobs and sessions to that point in time, which is the behavior you want for a disaster-recovery restore (no half-applied jobs from "the future" relative to the restored data).
- It avoids a real failure mode: if sessions/cache lived in Redis while data lived in sqlite, restoring a backup would silently leave stale sessions and cache entries pointing at data that no longer matches — Redis has no idea a restore just happened.

The cost: no queue/cache isolation from the main database's write load, and Redis sits there provisioned but idle unless someone opts in per-environment. That's an acceptable trade for this app's actual traffic (a property-management back office, not a high-throughput queue workload) — revisit if either job volume or cache churn ever becomes large enough to contend with normal request traffic on the same sqlite file.
