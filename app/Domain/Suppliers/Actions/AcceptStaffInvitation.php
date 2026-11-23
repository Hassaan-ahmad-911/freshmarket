<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Shared\Enums\Role;
use App\Domain\Suppliers\Models\SupplierInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Turns an invitation into a staff membership.
 *
 * Rule violations are thrown as ValidationException: Laravel automatically
 * turns that into "go back to the form and show this error message".
 */
class AcceptStaffInvitation
{
    /** The invitee already has an account and is logged in. */
    public function forExistingUser(SupplierInvitation $invitation, User $user): User
    {
        $this->guard($invitation);

        if (strcasecmp($user->email, $invitation->email) !== 0) {
            throw ValidationException::withMessages(['invitation' => 'This invitation was sent to a different email address.']);
        }

        if ($user->supplier_id !== null) {
            throw ValidationException::withMessages(['invitation' => 'Your account already belongs to a store.']);
        }

        if ($user->isPlatformAdmin()) {
            throw ValidationException::withMessages(['invitation' => 'Admin accounts cannot join a store.']);
        }

        return DB::transaction(fn () => $this->attach($invitation, $user));
    }

    /** The invitee has no account yet — create one as part of accepting. */
    public function forNewUser(SupplierInvitation $invitation, string $name, string $password): User
    {
        $this->guard($invitation);

        if (User::where('email', $invitation->email)->exists()) {
            throw ValidationException::withMessages(['invitation' => 'An account with this email already exists. Please log in first.']);
        }

        return DB::transaction(function () use ($invitation, $name, $password) {
            $user = User::create([
                'name' => $name,
                'email' => $invitation->email,
                'password' => $password,
            ]);

            // Clicking the emailed link proves they own the address.
            $user->forceFill(['email_verified_at' => now()])->save();

            return $this->attach($invitation, $user);
        });
    }

    private function guard(SupplierInvitation $invitation): void
    {
        if (! $invitation->isUsable()) {
            throw ValidationException::withMessages(['invitation' => 'This invitation has expired or was already used.']);
        }
    }

    private function attach(SupplierInvitation $invitation, User $user): User
    {
        $user->forceFill(['supplier_id' => $invitation->supplier_id])->save();
        $user->assignRole(Role::SupplierStaff->value);
        $invitation->update(['accepted_at' => now()]);

        return $user;
    }
}
