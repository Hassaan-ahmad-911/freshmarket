<?php

namespace App\Domain\Payments\Enums;

enum RefundStatus: string
{
    case Pending = 'pending';       // sent to Stripe, not confirmed yet
    case Succeeded = 'succeeded';
    case Failed = 'failed';         // Stripe refused — nothing was refunded, totals were restored

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Succeeded => 'bg-emerald-100 text-emerald-800',
            self::Failed => 'bg-red-100 text-red-800',
        };
    }
}
