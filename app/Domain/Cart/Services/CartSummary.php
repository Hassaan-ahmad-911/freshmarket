<?php

namespace App\Domain\Cart\Services;

use App\Domain\Shared\Money\Money;
use Illuminate\Support\Collection;

/** Everything the basket and checkout pages need, already calculated. */
final readonly class CartSummary
{
    /** @param  Collection<int, CartGroup>  $groups */
    public function __construct(public Collection $groups) {}

    public function isEmpty(): bool
    {
        return $this->groups->isEmpty();
    }

    public function itemCount(): int
    {
        return $this->groups->sum(fn (CartGroup $g) => $g->items->sum('quantity'));
    }

    public function subtotal(): Money
    {
        return Money::sum($this->groups->map->subtotal);
    }

    public function deliveryTotal(): Money
    {
        return Money::sum($this->groups->map->deliveryFee);
    }

    public function total(): Money
    {
        return $this->subtotal()->add($this->deliveryTotal());
    }

    public function canCheckout(): bool
    {
        return ! $this->isEmpty() && $this->groups->every(fn (CartGroup $g) => $g->meetsMinimum());
    }

    /** product_id => quantity, the input PlaceOrder expects. */
    public function lines(): array
    {
        return $this->groups
            ->flatMap(fn (CartGroup $g) => $g->items)
            ->mapWithKeys(fn ($item) => [$item->product_id => $item->quantity])
            ->all();
    }
}
