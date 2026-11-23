<?php

namespace App\Domain\Orders\Models;

use App\Domain\Catalog\Models\Product;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Money $unit_price
 * @property Money $line_total
 * @property Money $commission
 */
#[Fillable([
    'order_id', 'supplier_order_id', 'product_id', 'product_name', 'unit', 'unit_price', 'quantity', 'line_total', 'commission',
    'refunded_quantity', 'refunded_amount', 'refunded_commission',
])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return [
            'unit_price' => MoneyCast::class,
            'line_total' => MoneyCast::class,
            'commission' => MoneyCast::class,
            'refunded_amount' => MoneyCast::class,
            'refunded_commission' => MoneyCast::class,
            'quantity' => 'integer',
            'refunded_quantity' => 'integer',
        ];
    }

    public function refundableQuantity(): int
    {
        return $this->quantity - $this->refunded_quantity;
    }

    /**
     * The platform's commission on $quantity units of this line.
     *
     * The line's commission was rounded once at checkout. Refunding part of the
     * line takes a proportional share (rounded half-up); refunding the LAST units
     * takes whatever commission is left, so the shares always add up exactly.
     */
    public function commissionFor(int $quantity): Money
    {
        if ($quantity >= $this->refundableQuantity()) {
            return $this->commission->subtract($this->refunded_commission);
        }

        $total = $this->commission->amount;

        return Money::of(intdiv(2 * $total * $quantity + $this->quantity, 2 * $this->quantity));
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
