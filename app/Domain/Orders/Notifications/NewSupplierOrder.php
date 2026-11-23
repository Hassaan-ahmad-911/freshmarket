<?php

namespace App\Domain\Orders\Notifications;

use App\Domain\Orders\Models\SupplierOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupplierOrder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupplierOrder $supplierOrder) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New order {$this->supplierOrder->number}")
            ->line("You have a new paid order worth {$this->supplierOrder->total} (your payout: {$this->supplierOrder->payout_amount}).")
            ->action('View order', route('supplier.orders.show', $this->supplierOrder));
    }
}
