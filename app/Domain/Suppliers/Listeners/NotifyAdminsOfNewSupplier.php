<?php

namespace App\Domain\Suppliers\Listeners;

use App\Domain\Shared\Enums\Role;
use App\Domain\Suppliers\Events\SupplierRegistered;
use App\Domain\Suppliers\Notifications\NewSupplierAwaitingApproval;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsOfNewSupplier
{
    public function handle(SupplierRegistered $event): void
    {
        Notification::send(
            User::role(Role::PlatformAdmin->value)->get(),
            new NewSupplierAwaitingApproval($event->supplier),
        );
    }
}
