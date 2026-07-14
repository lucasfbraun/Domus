<?php

namespace App\Enums;

enum SignatureStatus: string
{
    case NotGenerated = 'not_generated';
    case AwaitingSignature = 'awaiting_signature';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
