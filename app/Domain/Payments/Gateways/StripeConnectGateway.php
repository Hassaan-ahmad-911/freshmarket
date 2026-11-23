<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Orders\Models\Order;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\SupplierOrder;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\Payment;
use Carbon\CarbonImmutable;
use Stripe\StripeClient;

/**
 * Real Stripe, using Connect "separate charges and transfers":
 *
 *  1. The customer pays ONE charge to the PLATFORM account (Checkout Session),
 *     tagged with transfer_group = "order_42".
 *  2. After the payment_intent.succeeded webhook, the platform creates one
 *     Transfer per supplier, to their connected account, with the same group.
 *  3. A partial refund refunds the customer from the platform and reverses
 *     (part of) only that supplier's transfer.
 */
class StripeConnectGateway implements PaymentGateway
{
    /** Stripe requires a Checkout Session to live at least 30 minutes. */
    public const CHECKOUT_LIFETIME_MINUTES = 35;

    public function __construct(private StripeClient $stripe) {}

    public function name(): string
    {
        return 'stripe';
    }

    public function createCheckoutSession(Order $order, Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $order->loadMissing(['supplierOrders.supplier', 'supplierOrders.items', 'customer']);
        $currency = strtolower($order->currency);

        // One line per item, plus one delivery line per supplier — they add up to order.total exactly.
        $lineItems = [];
        foreach ($order->supplierOrders as $supplierOrder) {
            /** @var SupplierOrder $supplierOrder */
            foreach ($supplierOrder->items as $item) {
                /** @var OrderItem $item */
                $lineItems[] = [
                    'quantity' => $item->quantity,
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $item->unit_price->amount,
                        'product_data' => ['name' => "{$item->product_name} — {$supplierOrder->supplier->name}"],
                    ],
                ];
            }

            if ($supplierOrder->delivery_fee->isPositive()) {
                $lineItems[] = [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $supplierOrder->delivery_fee->amount,
                        'product_data' => ['name' => "Delivery — {$supplierOrder->supplier->name}"],
                    ],
                ];
            }
        }

        $expiresAt = CarbonImmutable::now()->addMinutes(self::CHECKOUT_LIFETIME_MINUTES);
        $metadata = ['order_id' => (string) $order->id, 'order_number' => $order->number, 'payment_id' => (string) $payment->id];

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->customer->email,
            'client_reference_id' => (string) $order->id,
            'metadata' => $metadata,
            'payment_intent_data' => [
                'description' => "FreshMarket order {$order->number}",
                'transfer_group' => $order->transferGroup(),
                'metadata' => $metadata, // copied onto the PaymentIntent → available in payment_intent.succeeded
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'expires_at' => $expiresAt->getTimestamp(),
        ], ['idempotency_key' => "checkout-payment-{$payment->id}-".$expiresAt->getTimestamp()]);

        return new CheckoutSession($session->id, $session->url, $expiresAt);
    }

    public function createTransfer(
        int $amount,
        string $currency,
        string $destinationAccount,
        string $sourceChargeId,
        string $transferGroup,
        string $idempotencyKey,
        array $metadata = [],
    ): string {
        $transfer = $this->stripe->transfers->create([
            'amount' => $amount,
            'currency' => strtolower($currency),
            'destination' => $destinationAccount,
            'transfer_group' => $transferGroup,
            // Ties the transfer to the customer's charge: it can be made immediately,
            // even while the charge's funds are still "pending" in the platform balance.
            'source_transaction' => $sourceChargeId,
            'metadata' => $metadata,
        ], ['idempotency_key' => $idempotencyKey]);

        return $transfer->id;
    }

    public function refund(string $paymentIntentId, int $amount, string $idempotencyKey, array $metadata = []): GatewayRefund
    {
        $refund = $this->stripe->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $amount,
            'reason' => 'requested_by_customer',
            'metadata' => $metadata,
        ], ['idempotency_key' => $idempotencyKey]);

        return new GatewayRefund($refund->id, (string) $refund->status);
    }

    public function reverseTransfer(string $transferId, int $amount, string $idempotencyKey, array $metadata = []): string
    {
        $reversal = $this->stripe->transfers->createReversal($transferId, [
            'amount' => $amount,
            'metadata' => $metadata,
        ], ['idempotency_key' => $idempotencyKey]);

        return $reversal->id;
    }

    /**
     * Connected accounts use Stripe's Accounts v2 API (new platforms can no longer
     * create v1 accounts). A supplier is a "recipient": it only needs the
     * stripe_balance.stripe_transfers capability to receive our transfers.
     * The resulting acct_... id works with the v1 Transfers API as `destination`.
     */
    public function createConnectedAccount(string $email, string $businessName, array $metadata = []): string
    {
        $account = $this->stripe->v2->core->accounts->create([
            'contact_email' => $email,
            'display_name' => $businessName,
            'dashboard' => 'express', // supplier gets the Stripe Express dashboard
            'identity' => ['country' => config('services.stripe.connect_country', 'US')],
            'configuration' => [
                'recipient' => ['capabilities' => ['stripe_balance' => ['stripe_transfers' => ['requested' => true]]]],
            ],
            'defaults' => [
                'currency' => strtolower(config('freshmarket.currency', 'usd')),
                // Recipient-only accounts: the platform collects fees and covers losses.
                'responsibilities' => ['fees_collector' => 'application', 'losses_collector' => 'application'],
            ],
            'metadata' => $metadata,
        ]);

        return $account->id;
    }

    public function createOnboardingLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        return $this->stripe->v2->core->accountLinks->create([
            'account' => $accountId,
            'use_case' => [
                'type' => 'account_onboarding',
                'account_onboarding' => [
                    'refresh_url' => $refreshUrl,
                    'return_url' => $returnUrl,
                ],
            ],
        ])->url;
    }

    public function retrieveAccount(string $accountId): ConnectedAccountStatus
    {
        $account = $this->stripe->v2->core->accounts->retrieve($accountId, ['include' => ['configuration.recipient', 'requirements']]);

        return ConnectedAccountStatus::fromStripeV2($account->toArray());
    }
}
