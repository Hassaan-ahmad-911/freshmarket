<?php

use App\Domain\Shared\Enums\Role;
use App\Http\Controllers\Admin\SupplierApprovalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin area — TEMPORARY
|--------------------------------------------------------------------------
| In Phase 6 the Filament admin panel takes over /admin and this file (plus
| Admin\SupplierApprovalController and its view) is deleted. Until then,
| admins get a simple supplier approval screen.
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:'.Role::PlatformAdmin->value])
    ->group(function () {
        Route::get('/', [SupplierApprovalController::class, 'index'])->name('home');
        Route::post('/suppliers/{supplier}/approve', [SupplierApprovalController::class, 'approve'])->name('suppliers.approve');
        Route::post('/suppliers/{supplier}/suspend', [SupplierApprovalController::class, 'suspend'])->name('suppliers.suspend');
    });
