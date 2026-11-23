<?php

namespace App\Domain\Payouts\Listeners;

use App\Domain\Orders\Events\OrderPaid;
use App\Domain\Payouts\Jobs\CreateTransfersForOrder;

class QueueSupplierTransfers
{
    public function handle(OrderPaid $event): void
    {
        CreateTransfersForOrder::dispatch($event->order->id);
    }
}
