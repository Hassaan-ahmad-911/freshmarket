<?php

use App\Domain\Shared\Enums\Permission;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\FakeCheckoutController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\OrderController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\StoreController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Public shop — anyone can browse.
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
Route::get('/stores/{supplier:slug}', [StoreController::class, 'show'])->name('stores.show');

/*
| Staff invitation links (from email). The token in the URL is the secret.
*/
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::post('/invitations/{token}', [InvitationController::class, 'accept'])
    ->middleware('throttle:10,1')
    ->name('invitations.accept');

/*
| Customers: basket, checkout and order history.
| (`permission:place orders` keeps suppliers and admins out of the buying flow.)
*/
Route::middleware(['auth', 'verified', 'permission:'.Permission::PlaceOrders->value])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/products/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/pay', [OrderController::class, 'pay'])->name('orders.pay');

    // Offline stand-in for Stripe Checkout (404 when real Stripe keys are configured).
    Route::get('/fake-checkout/{payment}', [FakeCheckoutController::class, 'show'])->name('fake-checkout.show');
    Route::post('/fake-checkout/{payment}', [FakeCheckoutController::class, 'pay'])->name('fake-checkout.pay');
});

/*
| Stripe webhooks (signature-verified, CSRF-exempt — see bootstrap/app.php).
*/
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

/*
| Signed-in users.
*/
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
