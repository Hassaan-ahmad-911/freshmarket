<?php

namespace App\Domain\Catalog\Policies;

use App\Domain\Catalog\Models\Category;
use App\Domain\Shared\Enums\Permission;
use App\Models\User;

/** Categories are platform-wide: only admins manage them (Filament, Phase 6). */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageCategories->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageCategories->value);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can(Permission::ManageCategories->value);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can(Permission::ManageCategories->value) && ! $category->products()->withTrashed()->exists();
    }
}
