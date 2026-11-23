<?php

namespace App\Domain\Payouts\Enums;

enum TransferStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';             // money is in the supplier's connected account
    case Blocked = 'blocked';       // supplier can't receive transfers yet (onboarding unfinished)
    case Failed = 'failed';         // Stripe refused; will be retried
    case NotRequired = 'not_required'; // nothing left to pay (fully refunded before transfer)

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Not required',
            default => ucfirst($this->value),
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Paid => 'bg-emerald-100 text-emerald-800',
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Blocked, self::Failed => 'bg-red-100 text-red-800',
            self::NotRequired => 'bg-gray-100 text-gray-600',
        };
    }
}
