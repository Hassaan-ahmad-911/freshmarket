<?php

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Models\CartItem;
use App\Domain\Shared\Money\Money;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Support\Collection;

/** The part of the basket that will become ONE supplier order. */
final readonly class CartGroup
{
    /** @param  Collection<int, CartItem>  $items */
    public function __construct(
        public Supplier $supplier,
        public Collection $items,
        public Money $subtotal,
        public Money $deliveryFee,
    ) {}

    public function total(): Money
    {
        return $this->subtotal->add($this->deliveryFee);
    }

    public function meetsMinimum(): bool
    {
        return ! $this->subtotal->lessThan($this->supplier->min_order_amount);
    }

    /** How much more the customer must add to reach the store's minimum. */
    public function shortfall(): Money
    {
        return $this->meetsMinimum() ? Money::zero() : $this->supplier->min_order_amount->subtract($this->subtotal);
    }
}
