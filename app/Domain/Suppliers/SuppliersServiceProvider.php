<?php

namespace App\Domain\Suppliers;

use App\Domain\Shared\ModuleServiceProvider;
use App\Domain\Suppliers\Events\SupplierApproved;
use App\Domain\Suppliers\Events\SupplierRegistered;
use App\Domain\Suppliers\Events\SupplierSuspended;
use App\Domain\Suppliers\Listeners\NotifyAdminsOfNewSupplier;
use App\Domain\Suppliers\Listeners\NotifyOwnerOfStatusChange;
use App\Domain\Suppliers\Models\Supplier;
use App\Domain\Suppliers\Policies\SupplierPolicy;

/**
 * Suppliers module: Supplier stores, approval, staff and Stripe Connect onboarding.
 */
class SuppliersServiceProvider extends ModuleServiceProvider
{
    protected array $policies = [
        Supplier::class => SupplierPolicy::class,
    ];

    protected array $listen = [
        SupplierRegistered::class => [NotifyAdminsOfNewSupplier::class],
        SupplierApproved::class => [NotifyOwnerOfStatusChange::class],
        SupplierSuspended::class => [NotifyOwnerOfStatusChange::class],
    ];
}
