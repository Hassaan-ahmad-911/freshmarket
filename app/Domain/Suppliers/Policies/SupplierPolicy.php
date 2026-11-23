<?php

namespace App\Domain\Suppliers\Policies;

use App\Domain\Shared\Enums\Permission;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;

/**
 * Two checks in every rule:
 *  1. the permission  — "is this kind of user allowed to do this?"
 *  2. the ownership   — "is this store THEIR store?"
 * Missing either one means "no". That is what stops supplier A from
 * touching supplier B's data, even with a hand-crafted request.
 */
class SupplierPolicy
{
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can(Permission::ManageStore->value) && $supplier->hasMember($user);
    }

    public function manageStaff(User $user, Supplier $supplier): bool
    {
        return $user->can(Permission::ManageStaff->value) && $supplier->hasMember($user);
    }

    /** Admin-only actions. */
    public function approve(User $user, Supplier $supplier): bool
    {
        return $user->can(Permission::ManageSuppliers->value);
    }

    public function suspend(User $user, Supplier $supplier): bool
    {
        return $user->can(Permission::ManageSuppliers->value);
    }
}
