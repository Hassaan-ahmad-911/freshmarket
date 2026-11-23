<?php

namespace App\Domain\Orders\Models;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The parent order: one per checkout, one payment.
 *
 * @property OrderStatus $status
 * @property Money $subtotal
 * @property Money $delivery_total
 * @property Money $total
 * @property Money $commission_total
 */
#[Fillable([
    'number', 'user_id', 'status', 'currency', 'subtotal', 'delivery_total', 'total', 'commission_total',
    'delivery_name', 'delivery_phone', 'delivery_address', 'delivery_area', 'delivery_notes', 'paid_at', 'cancelled_at',
    'refunded_total',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => MoneyCast::class,
            'delivery_total' => MoneyCast::class,
            'total' => MoneyCast::class,
            'commission_total' => MoneyCast::class,
            'refunded_total' => MoneyCast::class,
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function supplierOrders(): HasMany
    {
        return $this->hasMany(SupplierOrder::class)->orderBy('id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** "order_42" — also used as Stripe's transfer_group to link the charge and its transfers. */
    public function transferGroup(): string
    {
        return 'order_'.$this->id;
    }
}
