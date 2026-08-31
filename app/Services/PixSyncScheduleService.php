<?php

namespace App\Services;

use App\Jobs\SyncPendingPixPaymentsJob;
use App\Models\PixSyncSetting;

/**
 * Decides whether {@see SyncPendingPixPaymentsJob} should
 * actually poll Mercado Pago on this tick — see
 * docs/adr/0013-configurable-pix-sync-schedule.md for why this is a
 * due-check called on a fixed per-minute tick rather than a dynamically
 * rebuilt cron expression (same reasoning as
 * docs/adr/0012-configurable-backup-schedule.md). The admin-configured
 * enabled flag and interval live on {@see PixSyncSetting}, not in code, so
 * changing them from Admin -> Configurações takes effect on the next tick
 * without a deploy.
 */
class PixSyncScheduleService
{
    public function isDue(): bool
    {
        $setting = PixSyncSetting::current();

        if (! $setting->enabled) {
            return false;
        }

        if ($setting->last_run_at === null) {
            return true;
        }

        $intervalMinutes = $setting->interval_unit->toMinutes($setting->interval_value);

        // isPast() is a strict "<" — exactly on the boundary (e.g. a
        // 5-minute interval and precisely 5 minutes have passed) must still
        // count as due, not wait for the next tick.
        return $setting->last_run_at->addMinutes($intervalMinutes)->lessThanOrEqualTo(now());
    }

    public function markRan(): void
    {
        PixSyncSetting::current()->update(['last_run_at' => now()]);
    }
}
