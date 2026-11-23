<?php

namespace App\Domain\Payments\Events;

use App\Domain\Payments\Models\Refund;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Stripe accepted a refund to the customer. Listeners (all synchronous, so the
 * supplier sees the result straight away): reverse that supplier's transfer,
 * put the stock back, email the customer.
 */
class RefundIssued
{
    use Dispatchable, SerializesModels;

    public function __construct(public Refund $refund) {}
}
