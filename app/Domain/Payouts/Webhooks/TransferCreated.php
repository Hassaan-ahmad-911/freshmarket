<?php

namespace App\Domain\Payouts\Webhooks;

use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\Webhooks\WebhookHandler;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Domain\Payouts\Models\Transfer;

/**
 * transfer.created → Stripe confirms a transfer exists.
 *
 * Normally we already recorded it when the API call returned. This covers the
 * rare case where Stripe created the transfer but our process died (or timed
 * out) before saving the id: the webhook fills the gap, so the transfer is
 * never retried — and never paid twice.
 */
class TransferCreated implements WebhookHandler
{
    public function handle(WebhookEvent $event): void
    {
        $stripeTransfer = $event->object();

        $transfer = Transfer::where('stripe_transfer_id', $stripeTransfer['id'] ?? null)->first()
            ?? Transfer::find((int) ($stripeTransfer['metadata']['transfer_id'] ?? 0));

        if ($transfer === null || $transfer->isPaid()) {
            return;
        }

        $transfer->update([
            'status' => TransferStatus::Paid,
            'stripe_transfer_id' => $stripeTransfer['id'],
            'amount' => (int) $stripeTransfer['amount'],
            'destination_account' => $stripeTransfer['destination'] ?? $transfer->destination_account,
            'failure_message' => null,
            'transferred_at' => now(),
        ]);
    }
}
