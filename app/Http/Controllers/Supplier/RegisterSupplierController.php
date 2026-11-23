<?php

namespace App\Http\Controllers\Supplier;

use App\Domain\Suppliers\Actions\RegisterSupplier;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\RegisterSupplierRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterSupplierController extends Controller
{
    public function create(): View
    {
        return view('supplier.auth.register');
    }

    public function store(RegisterSupplierRequest $request, RegisterSupplier $registerSupplier): RedirectResponse
    {
        $supplier = $registerSupplier->handle($request->validated());

        event(new Registered($supplier->owner)); // Breeze's email-verification hook
        Auth::login($supplier->owner);

        return redirect()->route('supplier.dashboard')
            ->with('status', 'Welcome! Set up your store while an admin reviews your application.');
    }
}
