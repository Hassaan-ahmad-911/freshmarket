<?php

namespace App\Domain\Payouts\Models;

use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payouts\Enums\TransferStatus;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money moved from the platform to ONE supplier for ONE supplier order.
 *
 * @property TransferStatus $status
 * @property Money $amount
 * @property Money $reversed_amount
 */
#[Fillable([
    'supplier_order_id', 'supplier_id', 'payment_id', 'status', 'amount', 'reversed_amount', 'currency',
    'destination_account', 'stripe_transfer_id', 'idempotency_key', 'failure_message', 'transferred_at',
])]
class Transfer extends Model
{
    protected function casts(): array
    {
        return [
            'status' => TransferStatus::class,
            'amount' => MoneyCast::class,
            'reversed_amount' => MoneyCast::class,
            'transferred_at' => 'datetime',
        ];
    }

    public function supplierOrder(): BelongsTo
    {
        return $this->belongsTo(SupplierOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isPaid(): bool
    {
        return $this->status === TransferStatus::Paid;
    }

    /** What the supplier keeps from this transfer after reversals. */
    public function netAmount(): Money
    {
        return $this->amount->subtract($this->reversed_amount);
    }
}
