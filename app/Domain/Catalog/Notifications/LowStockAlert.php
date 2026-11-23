<?php

namespace App\Domain\Catalog\Notifications;

use App\Domain\Catalog\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low stock: {$this->product->name}")
            ->line("Only {$this->product->stock} left of {$this->product->name}.")
            ->action('Restock now', route('supplier.products.edit', $this->product));
    }
}
