<?php

namespace App\Http\Controllers\Supplier;

use App\Domain\Suppliers\Actions\UpdateSupplierProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\UpdateStoreProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StoreProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $supplier = $request->user()->supplier;
        Gate::authorize('update', $supplier);

        return view('supplier.store.edit', [
            'supplier' => $supplier->load('deliveryAreas'),
        ]);
    }

    public function update(UpdateStoreProfileRequest $request, UpdateSupplierProfile $updateProfile): RedirectResponse
    {
        $updateProfile->handle(
            $request->user()->supplier,
            $request->profileData(),
            $request->deliveryAreas(),
            $request->file('logo'),
            $request->boolean('remove_logo'),
        );

        return back()->with('status', 'Store profile saved.');
    }
}
