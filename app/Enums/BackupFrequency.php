<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * How often {@see \App\Services\BackupScheduleService} should generate an
 * automatic database backup. `Disabled` is the default — automatic backups
 * are opt-in, matching how the manual "Backups" page already worked before
 * this existed (see docs/adr/0012-configurable-backup-schedule.md).
 */
enum BackupFrequency: string
{
    case Disabled = 'disabled';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Disabled => 'Desativado',
            self::Daily => 'Diário',
            self::Weekly => 'Semanal',
            self::Monthly => 'Mensal',
        };
    }

    /**
     * The next calendar day a backup is due, counted from $lastRunAt's
     * date (the time-of-day component is deliberately dropped — which
     * hour it runs in is BackupSetting::run_at_hour's job, checked
     * separately by {@see BackupScheduleService::isDue()}, so this only
     * has to reason about whole days). Never called for Disabled —
     * {@see BackupScheduleService::runIfDue()} short-circuits before
     * this.
     */
    public function nextDueAt(CarbonInterface $lastRunAt): CarbonInterface
    {
        $anchor = $lastRunAt->copy()->startOfDay();

        return match ($this) {
            self::Disabled => $anchor,
            self::Daily => $anchor->addDay(),
            self::Weekly => $anchor->addWeek(),
            self::Monthly => $anchor->addMonthNoOverflow(),
        };
    }
}
