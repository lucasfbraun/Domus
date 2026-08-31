<?php

namespace App\Models;

use App\Enums\SyncIntervalUnit;
use App\Jobs\SyncPendingPixPaymentsJob;
use App\Services\PixSyncScheduleService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (always id 1) holding the automatic Mercado Pago Pix-sync
 * schedule config — see {@see PixSyncScheduleService}, which
 * reads this to decide when {@see SyncPendingPixPaymentsJob}
 * should actually poll Mercado Pago instead of no-op'ing on that tick.
 *
 * Defaults (enabled, every 2 minutes) intentionally match the fixed
 * schedule this replaced (`->everyTwoMinutes()` in routes/console.php), so
 * installs that already had automatic sync running keep behaving the same
 * way until an admin visits Configurações and changes it.
 */
#[Fillable(['enabled', 'interval_value', 'interval_unit', 'last_run_at'])]
class PixSyncSetting extends Model
{
    public const DEFAULT_INTERVAL_VALUE = 2;

    public const DEFAULT_INTERVAL_UNIT = SyncIntervalUnit::Minutes;

    public const MIN_INTERVAL_VALUE = 1;

    public const MAX_INTERVAL_VALUE = 1440;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'interval_value' => 'integer',
            'interval_unit' => SyncIntervalUnit::class,
            'last_run_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'enabled' => true,
            'interval_value' => self::DEFAULT_INTERVAL_VALUE,
            'interval_unit' => self::DEFAULT_INTERVAL_UNIT,
        ]);
    }
}
