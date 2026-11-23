<?php

namespace App\Domain\Suppliers\Actions;

use App\Domain\Suppliers\Models\Supplier;
use App\Domain\Suppliers\Models\SupplierInvitation;
use App\Domain\Suppliers\Notifications\StaffInvitationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class InviteStaffMember
{
    public function handle(Supplier $supplier, User $invitedBy, string $email): SupplierInvitation
    {
        $email = Str::lower(trim($email));

        // Re-inviting the same address replaces the older, unused invitation.
        $supplier->invitations()->where('email', $email)->whereNull('accepted_at')->delete();

        $plainToken = Str::random(48);

        $invitation = $supplier->invitations()->create([
            'invited_by' => $invitedBy->id,
            'email' => $email,
            'token_hash' => SupplierInvitation::hashToken($plainToken),
            'expires_at' => now()->addDays(SupplierInvitation::VALID_FOR_DAYS),
        ]);

        // "On-demand" notification: the person may not have an account yet.
        Notification::route('mail', $email)
            ->notify(new StaffInvitationNotification($invitation, $plainToken));

        return $invitation;
    }
}
