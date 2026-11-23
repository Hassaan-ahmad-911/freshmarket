<?php

namespace App\Domain\Shared\Enums;

/**
 * The four kinds of user on the platform.
 *
 * The string values are the role names stored in the `roles` table
 * (spatie/laravel-permission), so they must never be renamed casually.
 */
enum Role: string
{
    case PlatformAdmin = 'platform-admin';
    case SupplierOwner = 'supplier-owner';
    case SupplierStaff = 'supplier-staff';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::PlatformAdmin => 'Platform Admin',
            self::SupplierOwner => 'Supplier Owner',
            self::SupplierStaff => 'Supplier Staff',
            self::Customer => 'Customer',
        };
    }

    /**
     * Which permissions each role receives.
     *
     * Permissions answer "may this kind of user do this kind of thing?".
     * Policies (added per module) answer the second question:
     * "...and is this particular record theirs?".
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::PlatformAdmin => [
                Permission::AccessAdminPanel,
                Permission::ManageSuppliers,
                Permission::ManageCategories,
                Permission::ManagePlatformSettings,
                Permission::ViewAllOrders,
                Permission::IssueRefunds,
            ],
            self::SupplierOwner => [
                Permission::ManageStore,
                Permission::ManageProducts,
                Permission::ManageSupplierOrders,
                Permission::ViewEarnings,
                Permission::ManageStaff,
                Permission::ManagePayoutAccount,
            ],
            self::SupplierStaff => [
                Permission::ManageProducts,
                Permission::ManageSupplierOrders,
            ],
            self::Customer => [
                Permission::PlaceOrders,
            ],
        };
    }

    /** Roles that work inside a supplier's store. */
    public static function supplierRoles(): array
    {
        return [self::SupplierOwner, self::SupplierStaff];
    }

    /** "supplier-owner|supplier-staff" — the format spatie's `role:` middleware expects. */
    public static function middlewareList(self ...$roles): string
    {
        return implode('|', array_map(fn (self $role) => $role->value, $roles));
    }
}
