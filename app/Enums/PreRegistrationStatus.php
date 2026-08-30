<?php

namespace App\Enums;

/**
 * Status of a tenant pre-registration invite.
 *
 * Pending (link generated, not yet filled) -> InReview (applicant
 * submitted the form) -> Approved (admin created the real Tenant) or
 * Rejected (admin declined; not resubmittable — a fresh invite must be
 * generated).
 */
enum PreRegistrationStatus: string
{
    case Pending = 'pending';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
