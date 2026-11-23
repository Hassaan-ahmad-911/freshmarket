<?php

namespace App\Domain\Suppliers\Notifications;

use App\Domain\Suppliers\Models\SupplierInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to an email address (not necessarily a registered user yet).
 * The plain token only exists in this email — the database stores its hash.
 */
class StaffInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupplierInvitation $invitation, public string $plainToken) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $store = $this->invitation->supplier->name;

        return (new MailMessage)
            ->subject("You're invited to join {$store} on FreshMarket")
            ->line("{$this->invitation->inviter->name} invited you to help manage {$store}.")
            ->action('Accept invitation', route('invitations.show', $this->plainToken))
            ->line('This link expires on '.$this->invitation->expires_at->toFormattedDayDateString().'.');
    }
}
