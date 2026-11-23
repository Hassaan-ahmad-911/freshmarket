<?php

namespace App\Domain\Orders\Notifications;

use App\Domain\Orders\Models\SupplierOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierOrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupplierOrder $supplierOrder) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $so = $this->supplierOrder;

        return (new MailMessage)
            ->subject("Order {$so->order->number}: {$so->supplier->name} — {$so->status->label()}")
            ->line("Your items from {$so->supplier->name} are now: {$so->status->label()}.")
            ->action('View your order', route('orders.show', $so->order));
    }
}
