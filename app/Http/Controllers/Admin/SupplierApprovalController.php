<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Suppliers\Actions\ApproveSupplier;
use App\Domain\Suppliers\Actions\SuspendSupplier;
use App\Domain\Suppliers\Models\Supplier;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * TEMPORARY — a minimal approve/suspend screen so new suppliers can be
 * approved before the Filament admin panel exists. Removed in Phase 6.
 */
class SupplierApprovalController extends Controller
{
    public function index(): View
    {
        return view('admin.suppliers.index', [
            'suppliers' => Supplier::with('owner')->orderByRaw("case status when 'pending' then 0 when 'approved' then 1 else 2 end")->latest()->get(),
        ]);
    }

    public function approve(Supplier $supplier, ApproveSupplier $approve): RedirectResponse
    {
        Gate::authorize('approve', $supplier);
        $approve->handle($supplier);

        return back()->with('status', "{$supplier->name} approved.");
    }

    public function suspend(Request $request, Supplier $supplier, SuspendSupplier $suspend): RedirectResponse
    {
        Gate::authorize('suspend', $supplier);
        $suspend->handle($supplier, $request->string('reason')->trim()->value() ?: null);

        return back()->with('status', "{$supplier->name} suspended.");
    }
}
