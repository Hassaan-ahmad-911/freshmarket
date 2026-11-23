<?php

namespace App\Domain\Payments\Listeners;

use App\Domain\Catalog\Actions\AdjustStock;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Payments\Events\RefundIssued;

/** Refunded goods were never sent, so they go back on the shelf. */
class RestockRefundedItems
{
    public function __construct(private AdjustStock $stock) {}

    public function handle(RefundIssued $event): void
    {
        foreach ($event->refund->lines as $line) {
            $product = OrderItem::find($line['item_id'])?->product;

            if ($product) {
                $this->stock->increase($product, (int) $line['quantity']);
            }
        }
    }
}
