<?php

namespace App\Domain\Orders\Events;

use App\Domain\Orders\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * The customer's payment is confirmed (by the Stripe webhook, never by the
 * browser redirect). Listeners: send confirmation emails, pay the suppliers.
 */
class OrderPaid
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
