<?php

namespace App\Enums;

enum CatalogStatus: string
{
    case Private = 'private';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privat',
            self::PendingReview => 'In Prüfung',
            self::Approved => 'Freigegeben',
            self::Rejected => 'Abgelehnt',
        };
    }
}
