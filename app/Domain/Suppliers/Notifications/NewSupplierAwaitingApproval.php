<?php

namespace App\Domain\Suppliers\Notifications;

use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Sent to every platform admin. ShouldQueue = sent by the queue worker, not during the request. */
class NewSupplierAwaitingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Supplier $supplier) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New supplier awaiting approval: {$this->supplier->name}")
            ->line("{$this->supplier->name} has registered on FreshMarket and is waiting for approval.")
            ->action('Review suppliers', url('/admin'));
    }
}
