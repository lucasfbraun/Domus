<?php

namespace App\Services;

use Carbon\Carbon;

class BillingCycle
{
    public static function todayInSaoPaulo(?Carbon $now = null): string
    {
        $now ??= Carbon::now('America/Sao_Paulo');

        return $now->timezone('America/Sao_Paulo')->format('Y-m-d');
    }

    public static function daysInMonth(int $year, int $month1Based): int
    {
        return (int) Carbon::create($year, $month1Based, 1)->daysInMonth;
    }

    /**
     * @return array{dueDateIso: string, daysUntilDue: int}
     */
    public static function resolveBillingCycleDueDate(int $dueDay, string $todayIso): array
    {
        [$year, $month] = array_map('intval', explode('-', $todayIso));
        $day = min($dueDay, self::daysInMonth($year, $month));
        $candidateIso = sprintf('%04d-%02d-%02d', $year, $month, $day);

        $candidateDate = Carbon::parse($candidateIso.'T12:00:00-03:00');
        $todayDate = Carbon::parse($todayIso.'T12:00:00-03:00');
        $diffDays = (int) round(($candidateDate->getTimestamp() - $todayDate->getTimestamp()) / 86_400);

        if ($diffDays < -10) {
            $nextMonth = $month + 1;
            $nextYear = $year;
            if ($nextMonth > 12) {
                $nextMonth = 1;
                $nextYear += 1;
            }
            $nextDay = min($dueDay, self::daysInMonth($nextYear, $nextMonth));
            $nextIso = sprintf('%04d-%02d-%02d', $nextYear, $nextMonth, $nextDay);
            $nextDate = Carbon::parse($nextIso.'T12:00:00-03:00');
            $nextDiff = (int) round(($nextDate->getTimestamp() - $todayDate->getTimestamp()) / 86_400);

            return ['dueDateIso' => $nextIso, 'daysUntilDue' => $nextDiff];
        }

        return ['dueDateIso' => $candidateIso, 'daysUntilDue' => $diffDays];
    }

    public static function formatReference(string $dueDateIso): string
    {
        $date = Carbon::parse($dueDateIso.'T12:00:00-03:00');
        $date->locale('pt_BR');
        $monthName = $date->translatedFormat('F');
        $capitalized = mb_strtoupper(mb_substr($monthName, 0, 1)).mb_substr($monthName, 1);

        return $capitalized.'/'.$date->year;
    }
}
