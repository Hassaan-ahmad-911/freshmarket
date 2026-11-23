<?php

namespace App\Domain\Orders\Services;

use App\Domain\Shared\Money\Money;

/**
 * The money rules of the marketplace, in one place, so the cart page and the
 * checkout can never disagree about a delivery fee or a commission.
 */
class OrderPricing
{
    /** Each supplier charges its own delivery fee, free above a threshold. */
    public function deliveryFee(Money $supplierSubtotal): Money
    {
        $threshold = (int) config('freshmarket.free_delivery_threshold');

        if ($threshold > 0 && $supplierSubtotal->amount >= $threshold) {
            return Money::zero($supplierSubtotal->currency);
        }

        return Money::of((int) config('freshmarket.default_delivery_fee'), $supplierSubtotal->currency);
    }

    /**
     * Commission is calculated PER LINE and rounded once per line.
     * Storing it per line lets a later refund of exactly that line reverse
     * exactly the right amount — no rounding drift between checkout and refund.
     */
    public function lineCommission(Money $lineTotal, int $basisPoints): Money
    {
        return $lineTotal->percentage($basisPoints);
    }
}
