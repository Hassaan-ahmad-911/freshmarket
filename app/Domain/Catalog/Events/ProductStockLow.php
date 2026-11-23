<?php

namespace App\Domain\Catalog\Events;

use App\Domain\Catalog\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Stock just dropped to (or below) the product's low-stock threshold. */
class ProductStockLow
{
    use Dispatchable, SerializesModels;

    public function __construct(public Product $product) {}
}
