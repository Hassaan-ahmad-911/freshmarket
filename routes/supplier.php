<?php

use App\Domain\Shared\Enums\Role;
use App\Http\Controllers\Supplier\OrderController;
use App\Http\Controllers\Supplier\PayoutsController;
use App\Http\Controllers\Supplier\ProductController;
use App\Http\Controllers\Supplier\ProductImageController;
use App\Http\Controllers\Supplier\RegisterSupplierController;
use App\Http\Controllers\Supplier\StaffController;
use App\Http\Controllers\Supplier\StoreProfileController;
use App\Http\Controllers\Supplier\SupplierDashboardController;
use App\Http\Middleware\EnsureUserBelongsToSupplier;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Supplier sign-up (guests only)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/supplier/register', [RegisterSupplierController::class, 'create'])->name('supplier.register');
    Route::post('/supplier/register', [RegisterSupplierController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Supplier area — /supplier/*
|--------------------------------------------------------------------------
| Three layers of protection:
|  1. `role:` middleware   — only supplier owners/staff get in
|  2. EnsureUserBelongsToSupplier — the account must be linked to a store
|  3. Policies (in controllers / form requests) — the record must be THEIR store's
*/

Route::prefix('supplier')
    ->name('supplier.')
    ->middleware(['auth', 'verified', 'role:'.Role::middlewareList(...Role::supplierRoles()), EnsureUserBelongsToSupplier::class])
    ->group(function () {
        Route::get('/', SupplierDashboardController::class)->name('dashboard');

        Route::get('/store', [StoreProfileController::class, 'edit'])->name('store.edit');
        Route::put('/store', [StoreProfileController::class, 'update'])->name('store.update');

        Route::resource('products', ProductController::class)->except('show');
        Route::patch('/products/{product}/toggle', [ProductController::class, 'toggle'])->name('products.toggle');
        Route::delete('/products/{product}/images/{image}', [ProductImageController::class, 'destroy'])
            ->scopeBindings()
            ->name('products.images.destroy');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{supplierOrder}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{supplierOrder}/status', [OrderController::class, 'updateStatus'])->name('orders.status');

        Route::get('/payouts', [PayoutsController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/connect', [PayoutsController::class, 'connect'])->name('payouts.connect');
        Route::get('/payouts/return', [PayoutsController::class, 'return'])->name('payouts.return');
        Route::get('/payouts/refresh', [PayoutsController::class, 'refresh'])->name('payouts.refresh');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff/invitations', [StaffController::class, 'invite'])->name('staff.invite');
        Route::delete('/staff/invitations/{invitation}', [StaffController::class, 'revokeInvitation'])->name('staff.invitations.destroy');
        Route::delete('/staff/{member}', [StaffController::class, 'remove'])->name('staff.destroy');
    });
