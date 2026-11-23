<?php

namespace App\Http\Controllers\Supplier;

use App\Domain\Suppliers\Actions\InviteStaffMember;
use App\Domain\Suppliers\Actions\RemoveStaffMember;
use App\Domain\Suppliers\Models\SupplierInvitation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\InviteStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $supplier = $request->user()->supplier;
        Gate::authorize('manageStaff', $supplier);

        return view('supplier.staff.index', [
            'supplier' => $supplier,
            'members' => $supplier->members()->orderBy('name')->get(),
            'invitations' => $supplier->invitations()->pending()->latest()->get(),
        ]);
    }

    public function invite(InviteStaffRequest $request, InviteStaffMember $inviteStaff): RedirectResponse
    {
        $invitation = $inviteStaff->handle($request->user()->supplier, $request->user(), $request->validated('email'));

        return back()->with('status', "Invitation sent to {$invitation->email}.");
    }

    public function revokeInvitation(Request $request, SupplierInvitation $invitation): RedirectResponse
    {
        $supplier = $request->user()->supplier;
        Gate::authorize('manageStaff', $supplier);
        abort_unless($invitation->supplier_id === $supplier->id, 404);

        $invitation->delete();

        return back()->with('status', 'Invitation cancelled.');
    }

    public function remove(Request $request, User $member, RemoveStaffMember $removeStaff): RedirectResponse
    {
        $supplier = $request->user()->supplier;
        Gate::authorize('manageStaff', $supplier);

        $removeStaff->handle($supplier, $member);

        return back()->with('status', "{$member->name} no longer has access to your store.");
    }
}
