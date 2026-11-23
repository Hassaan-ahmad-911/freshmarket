<?php

use App\Domain\Shared\Enums\Role;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supplier area — /supplier/*
|--------------------------------------------------------------------------
| Only supplier owners and supplier staff get past the `role:` middleware.
| Which *store's* data they can see is enforced later by Policies.
*/

Route::prefix('supplier')
    ->name('supplier.')
    ->middleware(['auth', 'verified', 'role:'.Role::middlewareList(...Role::supplierRoles())])
    ->group(function () {
        Route::view('/', 'supplier.dashboard')->name('dashboard');
    });
