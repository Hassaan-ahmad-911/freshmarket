<?php

namespace App\Domain\Payouts\Actions;

use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Domain\Payouts\Models\Transfer;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Pays ONE supplier their share of ONE order:
 *
 *   amount       = sub-order total − commission
 *   destination  = the supplier's connected account (acct_...)
 *   group        = "order_42" (links it to the customer's charge in Stripe)
 *
 * Safe to call any number of times: a paid transfer is never paid again, and
 * the idempotency key makes Stripe ignore a retried request.
 */
class TransferToSupplier
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(SupplierOrder $supplierOrder, Payment $payment): Transfer
    {
        // A short lock so two workers can't pay the same sub-order at the same moment.
        return Cache::lock("transfer-supplier-order-{$supplierOrder->id}", 30)
            ->block(10, fn () => $this->transfer($supplierOrder->fresh(), $payment));
    }

    private function transfer(SupplierOrder $supplierOrder, Payment $payment): Transfer
    {
        $transfer = Transfer::firstOrCreate(
            ['supplier_order_id' => $supplierOrder->id],
            [
                'supplier_id' => $supplierOrder->supplier_id,
                'payment_id' => $payment->id,
                'status' => TransferStatus::Pending,
                'amount' => $supplierOrder->payoutDue(),
                'currency' => $payment->currency,
                'idempotency_key' => "transfer-supplier-order-{$supplierOrder->id}",
            ],
        );

        if ($transfer->isPaid() || $transfer->status === TransferStatus::NotRequired) {
            return $transfer;
        }

        $amount = $supplierOrder->payoutDue();
        if (! $amount->isPositive()) {
            $transfer->update(['status' => TransferStatus::NotRequired, 'amount' => 0]);

            return $transfer;
        }

        $supplier = $supplierOrder->supplier;
        if (! $supplier->canReceiveTransfers()) {
            $transfer->update([
                'status' => TransferStatus::Blocked,
                'amount' => $amount,
                'failure_message' => 'The supplier has not finished Stripe onboarding yet. The transfer will be sent automatically once they do.',
            ]);

            return $transfer;
        }

        // Built from the customer's charge id (unique across every Stripe account and every
        // database reset) + this sub-order + the amount. A retry with the same amount is
        // deduplicated by Stripe; a different amount (after a refund) is a genuinely new request.
        $idempotencyKey = "transfer-{$payment->charge_id}-so{$supplierOrder->id}-{$amount->amount}";

        $transfer->update([
            'amount' => $amount,
            'destination_account' => $supplier->stripe_account_id,
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            $stripeTransferId = $this->gateway->createTransfer(
                amount: $amount->amount,
                currency: $payment->currency,
                destinationAccount: $supplier->stripe_account_id,
                sourceChargeId: (string) $payment->charge_id,
                transferGroup: $supplierOrder->order->transferGroup(),
                idempotencyKey: $idempotencyKey,
                metadata: [
                    'transfer_id' => (string) $transfer->id,
                    'order_number' => $supplierOrder->order->number,
                    'supplier_order_number' => $supplierOrder->number,
                ],
            );
        } catch (Throwable $e) {
            $transfer->update(['status' => TransferStatus::Failed, 'failure_message' => $e->getMessage()]);
            throw $e;
        }

        $transfer->update([
            'status' => TransferStatus::Paid,
            'stripe_transfer_id' => $stripeTransferId,
            'failure_message' => null,
            'transferred_at' => now(),
        ]);

        return $transfer;
    }
}
