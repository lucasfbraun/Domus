<?php

namespace App\Models;

use App\Services\ChargeScheduler;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (always id 1) holding system-wide billing configuration.
 * {@see ChargeScheduler} reads generation_day to decide when
 * the automatic monthly charge sweep is allowed to create charges.
 */
#[Fillable(['generation_day'])]
class BillingSetting extends Model
{
    public const DEFAULT_GENERATION_DAY = 1;

    public const MIN_GENERATION_DAY = 1;

    public const MAX_GENERATION_DAY = 28;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generation_day' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'generation_day' => self::DEFAULT_GENERATION_DAY,
        ]);
    }
}
