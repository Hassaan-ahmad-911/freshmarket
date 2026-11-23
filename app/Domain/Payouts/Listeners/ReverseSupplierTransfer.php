<?php

namespace App\Domain\Payouts\Listeners;

use App\Domain\Payments\Events\RefundIssued;
use App\Domain\Payouts\Actions\ReverseTransferForRefund;

/** Synchronous on purpose: the supplier's page shows the reversal right after clicking "Refund". */
class ReverseSupplierTransfer
{
    public function __construct(private ReverseTransferForRefund $reverse) {}

    public function handle(RefundIssued $event): void
    {
        $this->reverse->handle($event->refund);
    }
}
