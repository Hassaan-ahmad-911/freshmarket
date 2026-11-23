<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Breeze sends everyone to /dashboard after login. This controller then
 * forwards each user to the "home" of their role.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        return match (true) {
            $user->isPlatformAdmin() => redirect()->route('admin.home'),
            $user->isSupplierUser() => redirect()->route('supplier.dashboard'),
            default => view('dashboard'),
        };
    }
}
