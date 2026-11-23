<?php

namespace App\Domain\Orders\Notifications;

use App\Domain\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** "Order placed" email — sent once the payment is confirmed. */
class OrderConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('supplierOrders.supplier');
        $message = (new MailMessage)
            ->subject("Order {$order->number} confirmed")
            ->line("Thanks! We've received your payment of {$order->total}.")
            ->line('Your order is being prepared by:');

        foreach ($order->supplierOrders as $so) {
            $message->line("• {$so->supplier->name} — {$so->total}");
        }

        return $message->action('Track your order', route('orders.show', $order));
    }
}
