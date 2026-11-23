<?php

namespace App\Domain\Orders\Enums;

/** The PAYMENT state of the parent order (fulfilment lives on each supplier order). */
enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';   // never paid (abandoned checkout); stock was released

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::Paid => 'Paid',
            self::PartiallyRefunded => 'Partially refunded',
            self::Refunded => 'Refunded',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-amber-100 text-amber-800',
            self::Paid => 'bg-emerald-100 text-emerald-800',
            self::PartiallyRefunded => 'bg-sky-100 text-sky-800',
            self::Refunded => 'bg-gray-200 text-gray-700',
            self::Cancelled => 'bg-red-100 text-red-800',
        };
    }

    /** Money has been received (possibly partly refunded since). */
    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded, self::Refunded], true);
    }
}
