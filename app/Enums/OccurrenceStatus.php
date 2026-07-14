<?php

namespace App\Enums;

enum OccurrenceStatus: string
{
    case Open = 'open';
    case InReview = 'in_review';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aberta',
            self::InReview => 'Em análise',
            self::Resolved => 'Resolvida',
            self::Closed => 'Fechada',
        };
    }
}
