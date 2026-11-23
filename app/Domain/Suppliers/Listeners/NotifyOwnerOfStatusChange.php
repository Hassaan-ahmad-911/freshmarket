<?php

namespace App\Domain\Suppliers\Listeners;

use App\Domain\Suppliers\Events\SupplierApproved;
use App\Domain\Suppliers\Events\SupplierSuspended;
use App\Domain\Suppliers\Notifications\SupplierStatusChanged;

class NotifyOwnerOfStatusChange
{
    public function handle(SupplierApproved|SupplierSuspended $event): void
    {
        $reason = $event instanceof SupplierSuspended ? $event->reason : null;

        $event->supplier->owner->notify(new SupplierStatusChanged($event->supplier, $reason));
    }
}
