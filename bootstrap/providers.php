<?php

use App\Domain\Cart\CartServiceProvider;
use App\Domain\Catalog\CatalogServiceProvider;
use App\Domain\Orders\OrdersServiceProvider;
use App\Domain\Payments\PaymentsServiceProvider;
use App\Domain\Payouts\PayoutsServiceProvider;
use App\Domain\Suppliers\SuppliersServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,

    // Domain modules — each one registers its own policies, listeners and bindings.
    SuppliersServiceProvider::class,
    CatalogServiceProvider::class,
    CartServiceProvider::class,
    OrdersServiceProvider::class,
    PaymentsServiceProvider::class,
    PayoutsServiceProvider::class,
];
