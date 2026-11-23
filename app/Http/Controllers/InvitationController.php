<?php

namespace App\Http\Controllers;

use App\Domain\Suppliers\Actions\AcceptStaffInvitation;
use App\Domain\Suppliers\Models\SupplierInvitation;
use App\Http\Requests\AcceptInvitationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** The page a staff member lands on after clicking the link in their invitation email. */
class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = $this->findOrFail($token);

        return view('invitations.show', [
            'invitation' => $invitation->load('supplier'),
            'token' => $token,
        ]);
    }

    public function accept(AcceptInvitationRequest $request, string $token, AcceptStaffInvitation $accept): RedirectResponse
    {
        $invitation = $this->findOrFail($token);

        $user = $request->user()
            ? $accept->forExistingUser($invitation, $request->user())
            : $accept->forNewUser($invitation, $request->validated('name'), $request->validated('password'));

        Auth::login($user);

        return redirect()->route('supplier.dashboard')
            ->with('status', "You've joined {$invitation->supplier->name}.");
    }

    private function findOrFail(string $token): SupplierInvitation
    {
        $invitation = SupplierInvitation::findByToken($token);

        abort_if($invitation === null || ! $invitation->isUsable(), 404, 'This invitation has expired or was already used.');

        return $invitation;
    }
}
