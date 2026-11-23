<?php

namespace App\Domain\Orders\Policies;

use App\Domain\Orders\Models\Order;
use App\Domain\Shared\Enums\Permission;
use App\Models\User;

class OrderPolicy
{
    /** Customers see their own orders; admins see everything. */
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id || $user->can(Permission::ViewAllOrders->value);
    }
}
