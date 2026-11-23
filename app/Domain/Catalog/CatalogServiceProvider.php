<?php

namespace App\Domain\Catalog;

use App\Domain\Catalog\Events\ProductStockLow;
use App\Domain\Catalog\Listeners\SendLowStockAlert;
use App\Domain\Catalog\Models\Category;
use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Policies\CategoryPolicy;
use App\Domain\Catalog\Policies\ProductPolicy;
use App\Domain\Shared\ModuleServiceProvider;

/**
 * Catalog module: Products, categories, product images and stock.
 */
class CatalogServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        Product::class => ProductPolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    protected array $listen = [
        ProductStockLow::class => [SendLowStockAlert::class],
    ];
}
