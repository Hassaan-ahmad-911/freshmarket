<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Suppliers\Enums\SupplierStatus;
use App\Domain\Suppliers\Events\SupplierApproved;
use App\Domain\Suppliers\Models\Supplier;

class ApproveSupplier
{
    public function handle(Supplier $supplier): Supplier
    {
        if ($supplier->isApproved()) {
            return $supplier; // nothing to do — and no duplicate "approved" email
        }

        $supplier->update([
            'status' => SupplierStatus::Approved,
            'approved_at' => now(),
            'suspended_at' => null,
        ]);

        SupplierApproved::dispatch($supplier);

        return $supplier;
    }
}
