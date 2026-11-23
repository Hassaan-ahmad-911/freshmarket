<?php

namespace App\Domain\Payouts\Jobs;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payouts\Actions\TransferToSupplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs on the queue after an order is paid: one transfer per supplier.
 *
 * Each supplier is handled independently: if Stripe refuses supplier A's
 * transfer, supplier B is still paid, and the job comes back later for A.
 */
class CreateTransfersForOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $orderId) {}

    public function handle(TransferToSupplier $transferToSupplier): void
    {
        $order = Order::with('supplierOrders.supplier')->findOrFail($this->orderId);
        $payment = Payment::where('order_id', $order->id)->where('status', PaymentStatus::Succeeded)->first();

        if ($payment === null) {
            return; // not paid (yet) — nothing to transfer
        }

        $anyFailed = false;

        foreach ($order->supplierOrders as $supplierOrder) {
            try {
                $transferToSupplier->handle($supplierOrder, $payment);
            } catch (Throwable $e) {
                $anyFailed = true;
                report($e);
            }
        }

        if ($anyFailed && $this->attempts() < $this->tries) {
            $this->release(60 * $this->attempts()); // try the failed ones again later
        }
    }
}
