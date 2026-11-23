<?php

namespace App\Domain\Orders\Policies;

use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Shared\Enums\Permission;
use App\Models\User;

/**
 * A supplier user may only touch sub-orders of THEIR store.
 * The parent order and the other suppliers' sub-orders stay invisible.
 */
class SupplierOrderPolicy
{
    public function view(User $user, SupplierOrder $supplierOrder): bool
    {
        return $user->can(Permission::ManageSupplierOrders->value)
            && $user->supplier_id !== null
            && $supplierOrder->supplier_id === $user->supplier_id;
    }

    public function update(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->view($user, $supplierOrder);
    }

    /** Refund items / cancel: the store's own team, or a platform admin. */
    public function refund(User $user, SupplierOrder $supplierOrder): bool
    {
        return $this->view($user, $supplierOrder) || $user->can(Permission::IssueRefunds->value);
    }
}
