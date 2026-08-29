<?php

namespace App\Enums;

/**
 * Status of the generated contract document's signature workflow —
 * independent of ContractStatus (the lease term itself).
 *
 * NotGenerated -> AwaitingSignature (ContractDocumentService::generate())
 * -> InReview (tenant uploads a signed copy) -> Approved|Rejected (admin
 * review). Rejected can loop back to InReview on a new upload.
 */
enum SignatureStatus: string
{
    case NotGenerated = 'not_generated';
    case AwaitingSignature = 'awaiting_signature';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
