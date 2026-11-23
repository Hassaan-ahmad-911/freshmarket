<?php

namespace App\Domain\Payments\Listeners;

use App\Domain\Payments\Events\RefundIssued;
use App\Domain\Payments\Notifications\RefundProcessed;

class SendRefundNotification
{
    public function handle(RefundIssued $event): void
    {
        $event->refund->order->customer->notify(new RefundProcessed($event->refund));
    }
}
