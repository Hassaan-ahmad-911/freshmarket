<?php

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Models\CartItem;
use App\Domain\Catalog\Models\Product;
use App\Domain\Orders\Services\OrderPricing;
use App\Domain\Shared\Money\Money;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    public const MAX_QUANTITY = 99;

    public function __construct(private OrderPricing $pricing) {}

    public function cartFor(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    public function add(User $user, Product $product, int $quantity = 1): CartItem
    {
        if (! $product->isVisible()) {
            throw ValidationException::withMessages(['product' => 'This product is not available.']);
        }

        $item = $this->cartFor($user)->items()->firstOrNew(['product_id' => $product->id]);
        $newQuantity = min(($item->quantity ?? 0) + $quantity, self::MAX_QUANTITY);

        $this->assertInStock($product, $newQuantity);

        $item->quantity = $newQuantity;
        $item->save();

        return $item;
    }

    public function setQuantity(CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $item->delete();

            return;
        }

        $this->assertInStock($item->product, $quantity);
        $item->update(['quantity' => min($quantity, self::MAX_QUANTITY)]);
    }

    public function clear(User $user): void
    {
        Cart::where('user_id', $user->id)->first()?->items()->delete();
    }

    public function count(User $user): int
    {
        return (int) CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $user->id))->sum('quantity');
    }

    /**
     * Group the basket by supplier and price each group. Items that became
     * unavailable (product hidden, store suspended) are silently dropped.
     */
    public function summary(User $user): CartSummary
    {
        $items = $this->cartFor($user)->items()
            ->with(['product.supplier', 'product.category', 'product.images'])
            ->get();

        $unavailable = $items->filter(fn (CartItem $i) => ! $i->product || ! $i->product->isVisible());
        if ($unavailable->isNotEmpty()) {
            CartItem::whereKey($unavailable->modelKeys())->delete();
        }

        $groups = $items->diff($unavailable)
            ->groupBy(fn (CartItem $i) => $i->product->supplier_id)
            ->map(function ($supplierItems) {
                $subtotal = Money::sum($supplierItems->map(fn (CartItem $i) => $i->lineTotal()));

                return new CartGroup(
                    supplier: $supplierItems->first()->product->supplier,
                    items: $supplierItems->values(),
                    subtotal: $subtotal,
                    deliveryFee: $this->pricing->deliveryFee($subtotal),
                );
            })
            ->values();

        return new CartSummary($groups);
    }

    private function assertInStock(Product $product, int $quantity): void
    {
        if ($quantity > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => $product->stock === 0
                    ? "{$product->name} is out of stock."
                    : "Only {$product->stock} of {$product->name} available.",
            ]);
        }
    }
}
