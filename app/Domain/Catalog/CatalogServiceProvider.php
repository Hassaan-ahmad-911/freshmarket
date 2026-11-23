<?php

namespace App\Domain\Catalog;

use App\Domain\Shared\ModuleServiceProvider;

/**
 * Catalog module: Products, categories, product images and stock.
 *
 * Register this module's policies, event listeners and container
 * bindings here, never in AppServiceProvider.
 */
class CatalogServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [];

    /** @var array<class-string, list<class-string>> */
    protected array $listen = [];
}
