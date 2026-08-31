<?php

namespace App\Models;

use App\Enums\BackupFrequency;
use App\Services\BackupScheduleService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (always id 1) holding the automatic-backup schedule
 * config — see {@see BackupScheduleService}, which is what actually
 * reads this to decide when to run.
 */
#[Fillable(['frequency', 'retention_count', 'run_at_hour', 'last_run_at'])]
class BackupSetting extends Model
{
    public const DEFAULT_RETENTION_COUNT = 7;

    public const MIN_RETENTION_COUNT = 1;

    public const MAX_RETENTION_COUNT = 90;

    /** 3am: off-peak default, before the 09:00/10:00 billing/reminder jobs. */
    public const DEFAULT_RUN_AT_HOUR = 3;

    public const MIN_RUN_AT_HOUR = 0;

    public const MAX_RUN_AT_HOUR = 23;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'frequency' => BackupFrequency::class,
            'retention_count' => 'integer',
            'run_at_hour' => 'integer',
            'last_run_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'frequency' => BackupFrequency::Disabled,
            'retention_count' => self::DEFAULT_RETENTION_COUNT,
            'run_at_hour' => self::DEFAULT_RUN_AT_HOUR,
        ]);
    }
}
