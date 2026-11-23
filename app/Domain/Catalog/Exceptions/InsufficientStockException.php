<?php

namespace App\Domain\Catalog\Exceptions;

use App\Domain\Catalog\Models\Product;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(public readonly Product $product, public readonly int $requested)
    {
        parent::__construct("Only {$product->stock} of \"{$product->name}\" left, {$requested} requested.");
    }
}
