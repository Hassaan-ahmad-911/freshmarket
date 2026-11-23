<?php

namespace App\Domain\Payments\Actions;

use App\Domain\Orders\Enums\OrderStatus;
use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Enums\PaymentStatus;
use App\Domain\Payments\Models\Payment;
use Illuminate\Validation\ValidationException;

/**
 * Creates (or reuses) the payment page for an order and returns its URL.
 * Clicking "Pay" twice reuses the same open Checkout Session instead of creating a second one.
 */
class StartCheckout
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(Order $order): string
    {
        if ($order->status !== OrderStatus::PendingPayment) {
            throw ValidationException::withMessages(['order' => 'This order cannot be paid any more.']);
        }

        $payment = Payment::firstOrCreate(
            ['order_id' => $order->id],
            [
                'gateway' => $this->gateway->name(),
                'status' => PaymentStatus::Pending,
                'amount' => $order->total,
                'currency' => $order->currency,
            ],
        );

        if ($payment->hasUsableCheckout()) {
            return $payment->checkout_url;
        }

        $session = $this->gateway->createCheckoutSession(
            $order,
            $payment,
            successUrl: route('orders.show', ['order' => $order, 'checkout' => 'success']),
            cancelUrl: route('orders.show', ['order' => $order, 'checkout' => 'cancelled']),
        );

        $payment->update([
            'gateway' => $this->gateway->name(),
            'checkout_session_id' => $session->id,
            'checkout_url' => $session->url,
            'checkout_expires_at' => $session->expiresAt,
        ]);

        return $session->url;
    }
}
