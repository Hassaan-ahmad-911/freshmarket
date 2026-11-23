<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Suppliers\Enums\SupplierStatus;
use App\Domain\Suppliers\Events\SupplierSuspended;
use App\Domain\Suppliers\Models\Supplier;

/** Hides a store from customers. Also used to reject a pending application. */
class SuspendSupplier
{
    public function handle(Supplier $supplier, ?string $reason = null): Supplier
    {
        if ($supplier->isSuspended()) {
            return $supplier;
        }

        $supplier->update([
            'status' => SupplierStatus::Suspended,
            'suspended_at' => now(),
        ]);

        SupplierSuspended::dispatch($supplier, $reason);

        return $supplier;
    }
}
