<?php

namespace App\Domain\Orders\Listeners;

use App\Domain\Orders\Events\OrderPaid;
use App\Domain\Orders\Notifications\NewSupplierOrder;
use App\Domain\Orders\Notifications\OrderConfirmed;

/** Customer gets "order confirmed"; each supplier owner gets "new order" for their part only. */
class SendOrderPaidNotifications
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order->load('supplierOrders.supplier.owner', 'customer');

        $order->customer->notify(new OrderConfirmed($order));

        foreach ($order->supplierOrders as $supplierOrder) {
            $supplierOrder->supplier->owner->notify(new NewSupplierOrder($supplierOrder));
        }
    }
}
