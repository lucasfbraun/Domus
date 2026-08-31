# Admin-configurable schedule for the automatic Mercado Pago Pix sync

**Status**: accepted

`SyncPendingPixPaymentsJob` polls Mercado Pago for every Charge/Deposit still waiting on a Pix payment — a safety net alongside (or stand-in for) the webhook, so a missed webhook delivery doesn't leave a paid charge stuck as pending until someone notices and clicks "Sincronizar pagamento" by hand. It was hardcoded to run every 2 minutes (`Schedule::job(new SyncPendingPixPaymentsJob)->everyTwoMinutes()` in `routes/console.php`), with no way to turn it off or slow it down without a deploy.

## Same due-check pattern as the backup schedule

[ADR 0012](0012-configurable-backup-schedule.md) already established why this kind of "admin picks a period from the UI" setting is a periodic due-check rather than a dynamically rebuilt cron expression — Laravel's scheduler is defined once at boot from `routes/console.php`, not re-read per request. This feature reuses that shape: `PixSyncSetting` (a singleton row, like `BackupSetting`) holds `enabled`, `interval_value`, `interval_unit` (`minutes` or `hours`) and `last_run_at`; `PixSyncScheduleService::isDue()` compares `last_run_at + interval` against now; `routes/console.php` ticks the job every minute (the ceiling — the tightest the admin can configure) and the job no-ops unless a sync is actually due.

One difference from Backup: `BackupScheduleService::runIfDue()` bundles the due-check *and* the action (create + prune) into one call, because the action was already a single simple service call. Here the "action" is `SyncPendingPixPaymentsJob`'s existing Charge/Deposit polling logic, which needs `MercadoPagoService` and `ReminderService` injected — moving it into the schedule service would just relocate the same method-injected `handle()` elsewhere for no benefit. So `PixSyncScheduleService` only owns the decision (`isDue()`) and the bookkeeping (`markRan()`); the job calls both around its unchanged polling logic:

```php
public function handle(MercadoPagoService $mercadoPago, ReminderService $reminderService, PixSyncScheduleService $scheduler): void
{
    if (! $scheduler->isDue()) {
        return;
    }

    // ...unchanged Charge/Deposit polling...

    $scheduler->markRan();
}
```

## Minutes-or-hours interval instead of a fixed enum

Backup's frequency is a closed enum (`Daily`/`Weekly`/`Monthly`) because a backup only makes sense on calendar-day boundaries (paired with a configurable hour-of-day). A Pix sync has no such constraint — the admin might reasonably want a tight interval during an active rental season, or just a few checks a day for a quiet portfolio — so instead of a fixed set of choices, `interval_value` is a plain positive integer (1–1440, `PixSyncSetting::MIN/MAX_INTERVAL_VALUE`) paired with `SyncIntervalUnit` (`Minutes`/`Hours`) to pick the scale. `SyncIntervalUnit::toMinutes()` normalizes both to minutes for the actual comparison, so `isDue()` doesn't need to branch on the unit.

## Defaults preserve the previous always-on behavior

`PixSyncSetting::current()` defaults to `enabled = true`, `interval_value = 2`, `interval_unit = Minutes` — exactly what the hardcoded `->everyTwoMinutes()` did before. An install that already had automatic sync running keeps behaving identically after this migration, with nothing to reconfigure unless the admin actually wants to change it from Admin → Configurações.
