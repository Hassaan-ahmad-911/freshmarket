<?php

namespace App\Domain\Orders;

use App\Domain\Orders\Events\OrderPaid;
use App\Domain\Orders\Events\SupplierOrderStatusChanged;
use App\Domain\Orders\Listeners\NotifyCustomerOfStatusChange;
use App\Domain\Orders\Listeners\SendOrderPaidNotifications;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Orders\Policies\OrderPolicy;
use App\Domain\Orders\Policies\SupplierOrderPolicy;
use App\Domain\Shared\ModuleServiceProvider;

/**
 * Orders module: Parent orders, per-supplier sub-orders, order items and status changes.
 */
class OrdersServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        Order::class => OrderPolicy::class,
        SupplierOrder::class => SupplierOrderPolicy::class,
    ];

    protected array $listen = [
        SupplierOrderStatusChanged::class => [NotifyCustomerOfStatusChange::class],
        OrderPaid::class => [SendOrderPaidNotifications::class],
    ];
}
