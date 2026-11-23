<?php

namespace App\Domain\Orders\Enums;

/** The FULFILMENT state of one supplier's part of an order. */
enum SupplierOrderStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case Dispatched = 'dispatched';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Accepted, self::Preparing => 'bg-sky-100 text-sky-800',
            self::Dispatched => 'bg-indigo-100 text-indigo-800',
            self::Delivered => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-red-100 text-red-800',
        };
    }

    /**
     * The allowed next steps — a small "state machine".
     * Cancelling is handled separately because it also refunds money (Phase 5).
     *
     * @return list<self>
     */
    public function nextSteps(): array
    {
        return match ($this) {
            self::Pending => [self::Accepted],
            self::Accepted => [self::Preparing],
            self::Preparing => [self::Dispatched],
            self::Dispatched => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canMoveTo(self $next): bool
    {
        return in_array($next, $this->nextSteps(), true);
    }

    /** A supplier may cancel (and refund) until the goods have left. */
    public function isCancellable(): bool
    {
        return in_array($this, [self::Pending, self::Accepted, self::Preparing], true);
    }
}
