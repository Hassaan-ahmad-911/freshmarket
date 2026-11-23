<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Shared\Enums\Role;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveStaffMember
{
    public function handle(Supplier $supplier, User $member): void
    {
        if ($supplier->isOwnedBy($member)) {
            throw ValidationException::withMessages(['member' => 'The store owner cannot be removed.']);
        }

        if (! $supplier->hasMember($member)) {
            throw ValidationException::withMessages(['member' => 'This person does not work for your store.']);
        }

        DB::transaction(function () use ($member) {
            $member->forceFill(['supplier_id' => null])->save();
            $member->removeRole(Role::SupplierStaff->value);

            // They keep their account and can still shop as a customer.
            if (! $member->hasRole(Role::Customer)) {
                $member->assignRole(Role::Customer->value);
            }
        });
    }
}
