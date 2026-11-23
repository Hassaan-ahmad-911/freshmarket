<?php

namespace Database\Seeders;

use App\Domain\Shared\Enums\Permission as PermissionEnum;
use App\Domain\Shared\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every role and permission defined in the Role/Permission enums.
 *
 * Safe to run any number of times (findOrCreate + syncPermissions), so it
 * doubles as the "deploy" command after you add a new permission:
 *   php artisan db:seed --class=RolesAndPermissionsSeeder
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // spatie caches permissions; clear it so new ones are picked up.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (RoleEnum::cases() as $roleEnum) {
            Role::findOrCreate($roleEnum->value, 'web')->syncPermissions(
                array_map(fn (PermissionEnum $p) => $p->value, $roleEnum->permissions()),
            );
        }
    }
}
