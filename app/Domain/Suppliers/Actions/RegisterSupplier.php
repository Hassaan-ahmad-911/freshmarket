<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Shared\Enums\Role;
use App\Domain\Shared\Support\Slug;
use App\Domain\Suppliers\Enums\SupplierStatus;
use App\Domain\Suppliers\Events\SupplierRegistered;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a store owner account and their (pending) store in one go.
 *
 * Everything happens inside a DB transaction: if any step fails, nothing is
 * saved, so we never end up with an owner without a store or vice versa.
 */
class RegisterSupplier
{
    /**
     * @param  array{name: string, email: string, password: string, store_name: string, description?: ?string}  $data
     */
    public function handle(array $data): Supplier
    {
        $supplier = DB::transaction(function () use ($data) {
            $owner = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed by the User model's "hashed" cast
            ]);
            $owner->assignRole(Role::SupplierOwner->value);

            $supplier = Supplier::create([
                'owner_id' => $owner->id,
                'name' => $data['store_name'],
                'slug' => Slug::unique($data['store_name'], fn (string $slug) => Supplier::where('slug', $slug)->exists()),
                'description' => $data['description'] ?? null,
                'status' => SupplierStatus::Pending,
            ]);

            $owner->forceFill(['supplier_id' => $supplier->id])->save();

            return $supplier;
        });

        // Fired after the transaction committed, so listeners always see saved data.
        SupplierRegistered::dispatch($supplier);

        return $supplier;
    }
}
