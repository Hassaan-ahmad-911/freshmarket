<?php

namespace App\Domain\Cart;

use App\Domain\Shared\ModuleServiceProvider;

/**
 * Cart module: The customer's shopping cart (items from many suppliers).
 *
 * Register this module's policies, event listeners and container
 * bindings here, never in AppServiceProvider.
 */
class CartServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [];

    /** @var array<class-string, list<class-string>> */
    protected array $listen = [];
}
