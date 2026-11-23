<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Gateways\CheckoutSession;
use App\Domain\Payments\Gateways\ConnectedAccountStatus;
use App\Domain\Payments\Gateways\GatewayRefund;
use App\Domain\Payments\Models\Payment;

/**
 * Everything FreshMarket needs from a payment provider.
 *
 * The rest of the app only ever talks to this interface. Two implementations:
 *  - StripeConnectGateway — real Stripe (test mode) when STRIPE_SECRET is set
 *  - FakeGateway          — no network at all; used in tests and when no keys exist
 *
 * Every call that moves money takes an idempotency key: if the same call is
 * retried (timeout, queue retry, double click) the provider returns the FIRST
 * result instead of moving the money a second time.
 */
interface PaymentGateway
{
    /** "stripe" or "fake" — stored on each payment. */
    public function name(): string;

    /** A hosted payment page for the whole order (ONE charge on the platform). */
    public function createCheckoutSession(Order $order, Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession;

    /** Move money from the platform balance to a supplier's connected account. Returns the transfer id. */
    public function createTransfer(
        int $amount,
        string $currency,
        string $destinationAccount,
        string $sourceChargeId,
        string $transferGroup,
        string $idempotencyKey,
        array $metadata = [],
    ): string;

    /** Give money back to the customer's card (partial amounts allowed). */
    public function refund(string $paymentIntentId, int $amount, string $idempotencyKey, array $metadata = []): GatewayRefund;

    /** Pull (part of) a transfer back from the supplier's connected account. Returns the reversal id. */
    public function reverseTransfer(string $transferId, int $amount, string $idempotencyKey, array $metadata = []): string;

    /** Create a Stripe Connect Express account for a supplier. Returns the account id. */
    public function createConnectedAccount(string $email, string $businessName, array $metadata = []): string;

    /** A one-time link to Stripe's hosted onboarding for that account. */
    public function createOnboardingLink(string $accountId, string $refreshUrl, string $returnUrl): string;

    public function retrieveAccount(string $accountId): ConnectedAccountStatus;
}
