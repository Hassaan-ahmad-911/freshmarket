<?php

namespace App\Domain\Payments\Notifications;

use App\Domain\Payments\Models\Refund;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RefundProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Refund $refund) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $refund = $this->refund->loadMissing(['order', 'supplierOrder.supplier', 'item']);

        return (new MailMessage)
            ->subject("Refund of {$refund->amount} for order {$refund->order->number}")
            ->line("{$refund->supplierOrder->supplier->name} has refunded {$refund->amount} ({$refund->description()}).")
            ->lineIf($refund->reason !== null, "Reason: {$refund->reason}")
            ->line('The money goes back to the card you paid with, usually within 5–10 business days.')
            ->action('View your order', route('orders.show', $refund->order));
    }
}
