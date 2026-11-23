<?php

namespace App\Domain\Orders\Models;

use App\Domain\Orders\Enums\SupplierOrderStatus;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One supplier's slice of a parent order.
 *
 *   total          = subtotal + delivery_fee      (what the customer paid this supplier)
 *   commission     = Σ item commissions            (the platform's cut, on goods only)
 *   payout_amount  = total − commission            (what gets transferred to the supplier)
 *
 * @property SupplierOrderStatus $status
 * @property Money $subtotal
 * @property Money $delivery_fee
 * @property Money $total
 * @property Money $commission
 * @property Money $payout_amount
 */
#[Fillable([
    'number', 'order_id', 'supplier_id', 'status', 'commission_bps',
    'subtotal', 'delivery_fee', 'total', 'commission', 'payout_amount', 'cancelled_at',
    'refunded_amount', 'refunded_commission', 'delivery_refunded',
])]
class SupplierOrder extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SupplierOrderStatus::class,
            'commission_bps' => 'integer',
            'subtotal' => MoneyCast::class,
            'delivery_fee' => MoneyCast::class,
            'total' => MoneyCast::class,
            'commission' => MoneyCast::class,
            'payout_amount' => MoneyCast::class,
            'refunded_amount' => MoneyCast::class,
            'refunded_commission' => MoneyCast::class,
            'delivery_refunded' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    /** What the customer has paid for this sub-order after refunds. */
    public function netTotal(): Money
    {
        return $this->total->subtract($this->refunded_amount);
    }

    /** The platform's commission after refunds. */
    public function netCommission(): Money
    {
        return $this->commission->subtract($this->refunded_commission);
    }

    /** What is left to refund on this sub-order. */
    public function refundableAmount(): Money
    {
        return $this->netTotal();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * What the supplier should end up with for this sub-order right now:
     * the original payout minus the supplier's part of every refund.
     */
    public function payoutDue(): Money
    {
        return $this->netTotal()->subtract($this->netCommission());
    }
}
