<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\StoreController;
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
