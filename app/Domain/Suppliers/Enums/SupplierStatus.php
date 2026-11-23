<?php

namespace App\Domain\Suppliers\Enums;

enum SupplierStatus: string
{
    /** Just registered — can set up the store, but is invisible to customers. */
    case Pending = 'pending';

    /** Approved by an admin — store and products are visible and can sell. */
    case Approved = 'approved';

    /** Blocked by an admin — hidden from customers until re-approved. */
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending approval',
            self::Approved => 'Approved',
            self::Suspended => 'Suspended',
        };
    }

    /** Tailwind classes for a small coloured badge. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800',
            self::Approved => 'bg-emerald-100 text-emerald-800',
            self::Suspended => 'bg-red-100 text-red-800',
        };
    }
}
