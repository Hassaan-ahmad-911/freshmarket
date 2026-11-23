<?php

namespace App\Domain\Orders\Events;

use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Orders\Models\SupplierOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierOrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public SupplierOrder $supplierOrder, public SupplierOrderStatus $previous) {}
}
