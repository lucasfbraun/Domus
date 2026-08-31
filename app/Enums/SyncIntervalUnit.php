<?php

namespace App\Enums;

use App\Models\PixSyncSetting;

/**
 * The unit an admin picks {@see PixSyncSetting::$interval_value}
 * in — minutes for a tight poll, hours for a relaxed one.
 */
enum SyncIntervalUnit: string
{
    case Minutes = 'minutes';
    case Hours = 'hours';

    public function label(): string
    {
        return match ($this) {
            self::Minutes => 'Minutos',
            self::Hours => 'Horas',
        };
    }

    public function toMinutes(int $value): int
    {
        return match ($this) {
            self::Minutes => $value,
            self::Hours => $value * 60,
        };
    }
}
