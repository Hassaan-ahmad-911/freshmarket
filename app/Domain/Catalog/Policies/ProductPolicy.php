<?php

namespace App\Domain\Catalog\Policies;

use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Enums\Permission;
use App\Models\User;

/**
 * Supplier-side rules for products. (What CUSTOMERS can see is decided by
 * Product::visible(), not by this policy.)
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageProducts->value) && $user->supplier_id !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can(Permission::ManageProducts->value)
            && $user->supplier_id !== null
            && $product->supplier_id === $user->supplier_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
