<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Events\OrderPaid;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Called when Stripe confirms the money arrived (payment_intent.succeeded),
 * or by the fake checkout page when running without Stripe.
 *
 * Idempotent: the row is locked and checked first, so processing the same
 * success twice marks the order paid — and pays the suppliers — only once.
 */
class MarkPaymentSucceeded
{
    public function handle(int $orderId, string $paymentIntentId, ?string $chargeId, int $amountReceived): bool
    {
        $payment = DB::transaction(function () use ($orderId, $paymentIntentId, $chargeId, $amountReceived) {
            $payment = Payment::where('order_id', $orderId)->lockForUpdate()->first();

            if ($payment === null) {
                throw new RuntimeException("No payment found for order #{$orderId}.");
            }

            if ($payment->status === PaymentStatus::Succeeded) {
                return null; // already handled — nothing to do
            }

            $order = $payment->order;

            if ($amountReceived !== $order->total->amount) {
                Log::warning('Payment amount differs from order total', ['order' => $order->id, 'received' => $amountReceived, 'expected' => $order->total->amount]);
            }

            if ($order->status === OrderStatus::Cancelled) {
                Log::warning('Payment received for an order that was already released', ['order' => $order->id]);
            }

            $payment->update([
                'status' => PaymentStatus::Succeeded,
                'payment_intent_id' => $paymentIntentId,
                'charge_id' => $chargeId,
                'paid_at' => now(),
            ]);

            $order->update(['status' => OrderStatus::Paid, 'paid_at' => now(), 'cancelled_at' => null]);

            return $payment;
        });

        if ($payment === null) {
            return false;
        }

        // After commit: listeners (emails, supplier transfers) always see the saved state.
        OrderPaid::dispatch($payment->order->fresh());

        return true;
    }
}
