<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';

    case Confirmed = 'confirmed';

    case PartiallyPaid = 'partially_paid';

    case Paid = 'paid';

    case Cancelled = 'cancelled';

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array(
            $newStatus,
            $this->allowedTransitions(),
            true
        );
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [
                self::Confirmed,
                self::Cancelled,
            ],

            self::Confirmed => [
                self::PartiallyPaid,
                self::Paid,
                self::Cancelled,
            ],

            self::PartiallyPaid => [
                self::Paid,
                self::Cancelled,
            ],

            self::Paid => [],

            self::Cancelled => [],
        };
    }
}
