<?php

namespace App\Services;

class Finance
{
    public static function roundCents(float $value): float
    {
        return round($value * 100) / 100;
    }

    /**
     * @param  array{originalAmount: float, dueDate: string, status: string, graceDays: int, fineRate: float, monthlyInterestRate: float}  $input
     */
    public static function computeAmountDue(array $input, ?\DateTimeInterface $now = null): float
    {
        if ($input['status'] === 'paid') {
            return $input['originalAmount'];
        }

        $now ??= new \DateTimeImmutable('now', new \DateTimeZone('America/Sao_Paulo'));
        $due = new \DateTimeImmutable($input['dueDate'].'T12:00:00-03:00');
        $rawDaysLate = max(0, (int) floor(($now->getTimestamp() - $due->getTimestamp()) / 86_400));
        $billableDaysLate = max(0, $rawDaysLate - $input['graceDays']);

        if ($billableDaysLate <= 0) {
            return $input['originalAmount'];
        }

        $fine = $input['originalAmount'] * $input['fineRate'];
        $interest = $input['originalAmount'] * ($input['monthlyInterestRate'] / 30) * $billableDaysLate;

        return $input['originalAmount'] + $fine + $interest;
    }

    /**
     * @param  list<array{key: string, weight: float|int}>  $weights
     * @return array<string, float>
     */
    public static function splitByWeights(float $totalAmount, array $weights): array
    {
        $totalWeight = array_sum(array_column($weights, 'weight'));
        if ($totalWeight <= 0) {
            $totalWeight = count($weights);
        }

        $shares = [];
        $allocated = 0.0;
        $lastIndex = count($weights) - 1;

        foreach ($weights as $index => $item) {
            if ($index === $lastIndex) {
                $shares[$item['key']] = self::roundCents($totalAmount - $allocated);

                continue;
            }

            $share = self::roundCents(($totalAmount * $item['weight']) / $totalWeight);
            $shares[$item['key']] = $share;
            $allocated += $share;
        }

        return $shares;
    }
}
