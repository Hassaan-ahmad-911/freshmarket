<?php

namespace App\Domain\Orders;

use App\Domain\Shared\ModuleServiceProvider;

/**
 * Orders module: Parent orders, per-supplier sub-orders, order items and status changes.
 *
 * Register this module's policies, event listeners and container
 * bindings here, never in AppServiceProvider.
 */
class OrdersServiceProvider extends ModuleServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [];

    /** @var array<class-string, list<class-string>> */
    protected array $listen = [];
}
