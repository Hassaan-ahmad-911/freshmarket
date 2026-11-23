<?php

namespace App\Domain\Payments\Models;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Shared\Money\Money;
use App\Domain\Shared\Money\MoneyCast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The single charge for an order, made on the PLATFORM's Stripe account
 * ("separate charges and transfers"). Suppliers are paid afterwards by transfers.
 *
 * @property PaymentStatus $status
 * @property Money $amount
 */
#[Fillable([
    'order_id', 'gateway', 'status', 'amount', 'currency', 'checkout_session_id', 'checkout_url',
    'checkout_expires_at', 'payment_intent_id', 'charge_id', 'paid_at',
])]
class Payment extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => MoneyCast::class,
            'checkout_expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function hasUsableCheckout(): bool
    {
        return $this->status === PaymentStatus::Pending
            && $this->checkout_url !== null
            && $this->checkout_expires_at?->isFuture();
    }

    public function dashboardUrl(): ?string
    {
        return $this->gateway === 'stripe' && $this->payment_intent_id
            ? 'https://dashboard.stripe.com/test/payments/'.$this->payment_intent_id
            : null;
    }
}
