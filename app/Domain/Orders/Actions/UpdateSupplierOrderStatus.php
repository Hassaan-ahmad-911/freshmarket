<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Orders\Events\SupplierOrderStatusChanged;
use App\Domain\Orders\Models\SupplierOrder;
use Illuminate\Validation\ValidationException;

class UpdateSupplierOrderStatus
{
    public function handle(SupplierOrder $supplierOrder, SupplierOrderStatus $next): SupplierOrder
    {
        if (! $supplierOrder->order->status->isPaid()) {
            throw ValidationException::withMessages(['status' => 'This order has not been paid yet.']);
        }

        if (! $supplierOrder->status->canMoveTo($next)) {
            throw ValidationException::withMessages([
                'status' => "An order that is {$supplierOrder->status->label()} cannot be marked {$next->label()}.",
            ]);
        }

        $previous = $supplierOrder->status;
        $supplierOrder->update(['status' => $next]);

        SupplierOrderStatusChanged::dispatch($supplierOrder, $previous);

        return $supplierOrder;
    }
}
