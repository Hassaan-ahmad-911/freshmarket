<?php

namespace App\Domain\Suppliers\Notifications;

use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a store owner that their store was approved or suspended. */
class SupplierStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Supplier $supplier, public ?string $reason = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject("Your store is now {$this->supplier->status->label()}");

        if ($this->supplier->isApproved()) {
            return $message
                ->line("Good news — {$this->supplier->name} has been approved and is now visible to customers.")
                ->action('Go to your store', route('supplier.dashboard'));
        }

        return $message
            ->line("{$this->supplier->name} has been suspended and is hidden from customers.")
            ->lineIf($this->reason !== null, "Reason: {$this->reason}")
            ->line('Please contact support if you think this is a mistake.');
    }
}
