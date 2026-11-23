<?php

namespace App\Domain\Orders\Listeners;

use App\Domain\Orders\Events\SupplierOrderStatusChanged;
use App\Domain\Orders\Notifications\SupplierOrderStatusUpdated;

class NotifyCustomerOfStatusChange
{
    public function handle(SupplierOrderStatusChanged $event): void
    {
        $event->supplierOrder->order->customer->notify(new SupplierOrderStatusUpdated($event->supplierOrder));
    }
}
