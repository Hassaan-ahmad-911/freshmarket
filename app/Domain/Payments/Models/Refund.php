<?php

namespace App\Domain\Payments\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Payments\Enums\RefundStatus;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property RefundStatus $status
 * @property Money $amount back to the customer
 * @property Money $commission_amount commission the platform gives up
 * @property Money $reversal_amount pulled back from the supplier
 * @property Money $delivery_amount
 */
#[Fillable([
    'order_id', 'supplier_order_id', 'order_item_id', 'payment_id', 'initiated_by', 'type', 'quantity',
    'amount', 'commission_amount', 'reversal_amount', 'delivery_amount', 'lines', 'reason', 'status',
    'stripe_refund_id', 'reversal_status', 'stripe_reversal_id', 'failure_message',
])]
class Refund extends Model
{
    public const TYPE_ITEM = 'item';

    public const TYPE_SUPPLIER_ORDER = 'supplier_order';

    protected function casts(): array
    {
        return [
            'status' => RefundStatus::class,
            'amount' => MoneyCast::class,
            'commission_amount' => MoneyCast::class,
            'reversal_amount' => MoneyCast::class,
            'delivery_amount' => MoneyCast::class,
            'lines' => 'array',
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    /** "1 × Rainbow Carrots" or "Whole order cancelled". */
    public function description(): string
    {
        return $this->type === self::TYPE_ITEM
            ? "{$this->quantity} × ".($this->item?->product_name ?? 'item')
            : 'Whole order cancelled';
    }
}
