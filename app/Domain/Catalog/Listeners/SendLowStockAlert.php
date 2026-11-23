<?php

namespace App\Domain\Catalog\Listeners;

use App\Domain\Catalog\Events\ProductStockLow;
use App\Domain\Catalog\Notifications\LowStockAlert;

class SendLowStockAlert
{
    public function handle(ProductStockLow $event): void
    {
        $event->product->supplier->owner->notify(new LowStockAlert($event->product));
    }
}
