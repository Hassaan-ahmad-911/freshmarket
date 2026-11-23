<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Orders\Events\SupplierOrderStatusChanged;
use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Enums\RefundStatus;
use App\Domain\Payments\Events\RefundIssued;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Refund;
use App\Domain\Shared\Money\Money;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Partial refunds: one item (or some units of it), or a whole supplier order.
 *
 * Only THIS supplier order is touched. The customer gets money back on their
 * card, this supplier's transfer is reversed by their share, and every other
 * supplier in the same order is left exactly as it was.
 *
 * Three steps, so money can never be refunded twice or "lost":
 *   1. RESERVE (DB transaction, rows locked): check limits, add the amounts to
 *      the refunded counters, create a "pending" refund row. Two clicks at the
 *      same moment queue up on the lock; the second sees the updated counters.
 *   2. REFUND at Stripe with idempotency key "refund-{payment_intent}-{id}".
 *   3a. Stripe refused → RELEASE: undo the counters, mark the refund failed.
 *   3b. Stripe accepted → RefundIssued event → transfer reversal, restock, email.
 */
class IssueRefund
{
    public function __construct(private PaymentGateway $gateway) {}

    /** Refund $quantity units of one order line. */
    public function item(OrderItem $item, int $quantity, ?User $by = null, ?string $reason = null): Refund
    {
        $refund = DB::transaction(function () use ($item, $quantity, $by, $reason) {
            [$supplierOrder, $payment] = $this->lockAndCheck($item->supplier_order_id);

            $item = OrderItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $refundable = $item->refundableQuantity();

            if ($quantity < 1 || $quantity > $refundable) {
                throw ValidationException::withMessages([
                    'quantity' => $refundable === 0
                        ? "{$item->product_name} has already been fully refunded."
                        : "You can refund between 1 and {$refundable} of {$item->product_name}.",
                ]);
            }

            return $this->reserve($supplierOrder, $payment, Refund::TYPE_ITEM, [$this->line($item, $quantity)], Money::zero(), $by, $reason, $item->id, $quantity);
        });

        return $this->send($refund);
    }

    /** Cancel a supplier order: refund everything not yet refunded, including its delivery fee. */
    public function supplierOrder(SupplierOrder $supplierOrder, ?User $by = null, ?string $reason = null): ?Refund
    {
        $previousStatus = $supplierOrder->status;

        $refund = DB::transaction(function () use ($supplierOrder, $by, $reason) {
            [$supplierOrder, $payment] = $this->lockAndCheck($supplierOrder->id);

            if (! $supplierOrder->status->isCancellable()) {
                throw ValidationException::withMessages(['refund' => "An order that is {$supplierOrder->status->label()} can no longer be cancelled."]);
            }

            $lines = $supplierOrder->items()->lockForUpdate()->get()
                ->filter(fn (OrderItem $item) => $item->refundableQuantity() > 0)
                ->map(fn (OrderItem $item) => $this->line($item, $item->refundableQuantity()))
                ->values()
                ->all();
            $delivery = $supplierOrder->delivery_refunded ? Money::zero() : $supplierOrder->delivery_fee;

            if ($lines === [] && $delivery->isZero()) {
                return null; // everything was already refunded item by item
            }

            return $this->reserve($supplierOrder, $payment, Refund::TYPE_SUPPLIER_ORDER, $lines, $delivery, $by, $reason);
        });

        $refund = $refund ? $this->send($refund) : null;

        // Only cancel once the money has actually gone back to the customer.
        $supplierOrder->refresh()->update(['status' => SupplierOrderStatus::Cancelled, 'cancelled_at' => now()]);
        SupplierOrderStatusChanged::dispatch($supplierOrder, $previousStatus);

        return $refund;
    }

    /** @return array{SupplierOrder, Payment} */
    private function lockAndCheck(int $supplierOrderId): array
    {
        $supplierOrder = SupplierOrder::whereKey($supplierOrderId)->lockForUpdate()->firstOrFail();
        $order = Order::whereKey($supplierOrder->order_id)->lockForUpdate()->firstOrFail();

        if (! $order->status->isPaid()) {
            throw ValidationException::withMessages(['refund' => 'This order has not been paid.']);
        }

        if ($supplierOrder->status === SupplierOrderStatus::Cancelled) {
            throw ValidationException::withMessages(['refund' => 'This order has already been cancelled.']);
        }

        $payment = Payment::where('order_id', $order->id)->where('status', PaymentStatus::Succeeded)->first()
            ?? throw ValidationException::withMessages(['refund' => 'No successful payment was found for this order.']);

        return [$supplierOrder, $payment];
    }

    /** @return array{item_id: int, quantity: int, amount: int, commission: int} */
    private function line(OrderItem $item, int $quantity): array
    {
        return [
            'item_id' => $item->id,
            'quantity' => $quantity,
            'amount' => $item->unit_price->multiply($quantity)->amount,
            'commission' => $item->commissionFor($quantity)->amount,
        ];
    }

    private function reserve(
        SupplierOrder $supplierOrder,
        Payment $payment,
        string $type,
        array $lines,
        Money $delivery,
        ?User $by,
        ?string $reason,
        ?int $itemId = null,
        ?int $quantity = null,
    ): Refund {
        $amount = array_sum(array_column($lines, 'amount')) + $delivery->amount;
        $commission = array_sum(array_column($lines, 'commission')); // no commission on delivery

        $this->applyCounters($supplierOrder, $lines, $amount, $commission, $delivery, +1);

        return Refund::create([
            'order_id' => $supplierOrder->order_id,
            'supplier_order_id' => $supplierOrder->id,
            'order_item_id' => $itemId,
            'payment_id' => $payment->id,
            'initiated_by' => $by?->id,
            'type' => $type,
            'quantity' => $quantity,
            'amount' => $amount,
            'commission_amount' => $commission,
            'reversal_amount' => 0,
            'delivery_amount' => $delivery,
            'lines' => $lines,
            'reason' => $reason,
            'status' => RefundStatus::Pending,
        ]);
    }

    private function send(Refund $refund): Refund
    {
        $refund->loadMissing(['payment', 'order', 'supplierOrder']);

        try {
            $result = $this->gateway->refund(
                paymentIntentId: $refund->payment->payment_intent_id,
                amount: $refund->amount->amount,
                // PaymentIntent id + refund row: unique even if the database is re-seeded
                // (local ids restart at 1, but Stripe remembers keys for 24 hours).
                idempotencyKey: "refund-{$refund->payment->payment_intent_id}-{$refund->id}",
                metadata: [
                    'refund_id' => (string) $refund->id,
                    'order_number' => $refund->order->number,
                    'supplier_order_number' => $refund->supplierOrder->number,
                ],
            );
        } catch (Throwable $e) {
            $this->release($refund, $e->getMessage());

            throw ValidationException::withMessages(['refund' => 'The refund could not be processed: '.$e->getMessage()]);
        }

        if (in_array($result->status, ['failed', 'canceled'], true)) {
            $this->release($refund, "Stripe returned status \"{$result->status}\".");

            throw ValidationException::withMessages(['refund' => 'Stripe declined the refund.']);
        }

        $refund->update([
            'stripe_refund_id' => $result->id,
            'status' => $result->status === 'succeeded' ? RefundStatus::Succeeded : RefundStatus::Pending,
        ]);

        RefundIssued::dispatch($refund);

        return $refund->fresh();
    }

    /** Undo a reservation after Stripe refused: the totals go back to what they were. */
    private function release(Refund $refund, string $message): void
    {
        DB::transaction(function () use ($refund, $message) {
            $supplierOrder = SupplierOrder::whereKey($refund->supplier_order_id)->lockForUpdate()->firstOrFail();

            $this->applyCounters($supplierOrder, $refund->lines, $refund->amount->amount, $refund->commission_amount->amount, $refund->delivery_amount, -1);

            $refund->update(['status' => RefundStatus::Failed, 'failure_message' => $message]);
        });
    }

    /** $direction +1 = reserve, −1 = release. */
    private function applyCounters(SupplierOrder $supplierOrder, array $lines, int $amount, int $commission, Money $delivery, int $direction): void
    {
        foreach ($lines as $line) {
            OrderItem::whereKey($line['item_id'])->incrementEach([
                'refunded_quantity' => $direction * $line['quantity'],
                'refunded_amount' => $direction * $line['amount'],
                'refunded_commission' => $direction * $line['commission'],
            ]);
        }

        SupplierOrder::whereKey($supplierOrder->id)->incrementEach([
            'refunded_amount' => $direction * $amount,
            'refunded_commission' => $direction * $commission,
        ]);

        if ($delivery->isPositive()) {
            SupplierOrder::whereKey($supplierOrder->id)->update(['delivery_refunded' => $direction > 0]);
        }

        $order = Order::whereKey($supplierOrder->order_id)->firstOrFail();
        $refundedTotal = $order->refunded_total->amount + $direction * $amount;

        $order->update([
            'refunded_total' => $refundedTotal,
            'status' => match (true) {
                $refundedTotal <= 0 => OrderStatus::Paid,
                $refundedTotal >= $order->total->amount => OrderStatus::Refunded,
                default => OrderStatus::PartiallyRefunded,
            },
        ]);
    }
}
