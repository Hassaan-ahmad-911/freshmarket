<?php

namespace App\Domain\Orders\Actions;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Checkout reserves stock immediately. If the customer never pays, this
 * cancels the order and puts the stock back. Run by `orders:release-unpaid`.
 */
class ReleaseUnpaidOrder
{
    public function __construct(private AdjustStock $stock) {}

    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();

            if ($order->status !== OrderStatus::PendingPayment) {
                return; // paid in the meantime — leave it alone
            }

            foreach ($order->items()->with('product')->get() as $item) {
                if ($item->product) {
                    $this->stock->increase($item->product, $item->quantity);
                }
            }

            $order->supplierOrders()->update(['status' => SupplierOrderStatus::Cancelled, 'cancelled_at' => now()]);
            $order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
        });
    }
}
