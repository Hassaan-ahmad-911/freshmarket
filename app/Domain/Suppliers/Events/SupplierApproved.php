<?php

namespace App\Domain\Suppliers\Events;

use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public Supplier $supplier) {}
}
