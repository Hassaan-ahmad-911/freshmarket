<?php

namespace App\Domain\Suppliers\Events;

use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A new store signed up and is waiting for an admin to approve it. */
class SupplierRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public Supplier $supplier) {}
}
