<?php

use App\Domain\Shared\Enums\Role;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin area — TEMPORARY placeholder
|--------------------------------------------------------------------------
| In Phase 6 the Filament admin panel takes over /admin and this file is
| deleted. Until then, admins land on a simple page so login works end-to-end.
*/

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:'.Role::PlatformAdmin->value])
    ->group(function () {
        Route::view('/', 'admin.placeholder')->name('home');
    });
