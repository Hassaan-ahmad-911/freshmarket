<?php

namespace App\Domain\Payouts\Actions;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\Refund;
use App\Domain\Payouts\Models\Transfer;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Takes the supplier's share of a refund back from THEIR connected account.
 *
 * Instead of trusting the refund's own numbers, it compares what the supplier
 * HOLDS (transfer − earlier reversals) with what they SHOULD hold now
 * (SupplierOrder::payoutDue()) and reverses the difference. That makes it
 * correct in every order of events:
 *   - transfer already sent  → reverse the difference
 *   - transfer not sent yet  → nothing to reverse; it will be sent smaller
 *   - run twice / retried    → difference is already 0, nothing happens
 */
class ReverseTransferForRefund
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(Refund $refund): Refund
    {
        $supplierOrderId = $refund->supplier_order_id;

        // Same lock as TransferToSupplier: never reverse while a transfer is being created.
        return Cache::lock("transfer-supplier-order-{$supplierOrderId}", 30)->block(10, function () use ($refund) {
            $refund->refresh();
            $supplierOrder = $refund->supplierOrder()->first();
            $transfer = Transfer::where('supplier_order_id', $supplierOrder->id)->first();

            if ($transfer === null || ! $transfer->isPaid()) {
                $refund->update(['reversal_status' => 'not_needed', 'reversal_amount' => 0]);

                return $refund;
            }

            $excess = $transfer->netAmount()->subtract($supplierOrder->payoutDue());

            if (! $excess->isPositive()) {
                if ($refund->reversal_status === null) {
                    $refund->update(['reversal_status' => 'not_needed']);
                }

                return $refund;
            }

            try {
                $reversalId = $this->gateway->reverseTransfer(
                    transferId: $transfer->stripe_transfer_id,
                    amount: $excess->amount,
                    idempotencyKey: "reversal-{$transfer->stripe_transfer_id}-refund{$refund->id}-{$excess->amount}",
                    metadata: ['refund_id' => (string) $refund->id, 'supplier_order_number' => $supplierOrder->number],
                );
            } catch (Throwable $e) {
                // The customer already has their money back; the platform retries
                // the reversal later (payouts:retry). Nothing is lost, just delayed.
                $refund->update(['reversal_status' => 'failed', 'failure_message' => $e->getMessage()]);
                report($e);

                return $refund;
            }

            $transfer->update(['reversed_amount' => $transfer->reversed_amount->add($excess)]);
            $refund->update([
                'reversal_status' => 'succeeded',
                'reversal_amount' => $refund->reversal_amount->add($excess),
                'stripe_reversal_id' => $reversalId,
                'failure_message' => null,
            ]);

            return $refund;
        });
    }
}
