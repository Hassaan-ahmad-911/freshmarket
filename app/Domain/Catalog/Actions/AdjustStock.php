<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Catalog\Events\ProductStockLow;
use App\Domain\Catalog\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Models\Product;
use InvalidArgumentException;

/**
 * The ONLY place stock goes up or down because of orders (checkout in Phase 3,
 * cancellations/refunds in Phase 5).
 */
class AdjustStock
{
    /**
     * Remove stock, refusing to go below zero.
     *
     * The check and the update happen in ONE SQL statement
     *   UPDATE products SET stock = stock - 3 WHERE id = 1 AND stock >= 3
     * so two customers buying the last item at the same moment can't both win:
     * the database runs them one after the other and the second updates 0 rows.
     */
    public function decrease(Product $product, int $quantity): Product
    {
        $this->assertPositive($quantity);

        $updated = Product::whereKey($product->getKey())
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        $product->refresh();

        if ($updated === 0) {
            throw new InsufficientStockException($product, $quantity);
        }

        // Alert only when this change CROSSED the threshold, not on every sale below it.
        $stockBefore = $product->stock + $quantity;
        if ($stockBefore > $product->low_stock_threshold && $product->isLowStock()) {
            ProductStockLow::dispatch($product);
        }

        return $product;
    }

    /** Put stock back (e.g. an order was cancelled). */
    public function increase(Product $product, int $quantity): Product
    {
        $this->assertPositive($quantity);

        Product::withTrashed()->whereKey($product->getKey())->increment('stock', $quantity);

        return $product->refresh();
    }

    private function assertPositive(int $quantity): void
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }
    }
}
