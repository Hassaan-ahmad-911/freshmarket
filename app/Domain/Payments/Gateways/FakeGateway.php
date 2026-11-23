<?php

namespace App\Domain\Payments\Gateways;

use App\Domain\Orders\Models\Order;
use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A pretend payment provider. Used in tests, and in the app whenever no
 * STRIPE_SECRET is configured, so FreshMarket always runs.
 *
 * It behaves like Stripe where it matters: calls with the same idempotency
 * key return the same result instead of moving money twice. Every call is
 * recorded so tests can assert on exactly what would have been sent.
 */
class FakeGateway implements PaymentGateway
{
    /** @var array<string, array> idempotency key => recorded call */
    public array $transfers = [];

    public array $refunds = [];

    public array $reversals = [];

    public array $checkouts = [];

    /** Destination accounts whose transfers should fail (to test error handling). */
    public array $failingDestinations = [];

    public function name(): string
    {
        return 'fake';
    }

    public function createCheckoutSession(Order $order, Payment $payment, string $successUrl, string $cancelUrl): CheckoutSession
    {
        $session = new CheckoutSession(
            id: 'cs_fake_'.Str::random(16),
            url: route('fake-checkout.show', $payment),
            expiresAt: CarbonImmutable::now()->addMinutes(35),
        );
        $this->checkouts[] = ['order_id' => $order->id, 'amount' => $order->total->amount, 'session' => $session->id];

        return $session;
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
        if (in_array($destinationAccount, $this->failingDestinations, true)) {
            throw new RuntimeException("Fake transfer to {$destinationAccount} failed.");
        }

        $this->transfers[$idempotencyKey] ??= [
            'id' => 'tr_fake_'.Str::random(14),
            'amount' => $amount,
            'currency' => $currency,
            'destination' => $destinationAccount,
            'source_transaction' => $sourceChargeId,
            'transfer_group' => $transferGroup,
            'metadata' => $metadata,
        ];

        return $this->transfers[$idempotencyKey]['id'];
    }

    public function refund(string $paymentIntentId, int $amount, string $idempotencyKey, array $metadata = []): GatewayRefund
    {
        $this->refunds[$idempotencyKey] ??= [
            'id' => 're_fake_'.Str::random(14),
            'payment_intent' => $paymentIntentId,
            'amount' => $amount,
            'metadata' => $metadata,
        ];

        return new GatewayRefund($this->refunds[$idempotencyKey]['id'], 'succeeded');
    }

    public function reverseTransfer(string $transferId, int $amount, string $idempotencyKey, array $metadata = []): string
    {
        $this->reversals[$idempotencyKey] ??= [
            'id' => 'trr_fake_'.Str::random(14),
            'transfer' => $transferId,
            'amount' => $amount,
            'metadata' => $metadata,
        ];

        return $this->reversals[$idempotencyKey]['id'];
    }

    public function createConnectedAccount(string $email, string $businessName, array $metadata = []): string
    {
        return 'acct_fake_'.Str::random(12);
    }

    /** No hosted onboarding offline: go straight back to the return URL. */
    public function createOnboardingLink(string $accountId, string $refreshUrl, string $returnUrl): string
    {
        return $returnUrl;
    }

    public function retrieveAccount(string $accountId): ConnectedAccountStatus
    {
        return new ConnectedAccountStatus($accountId, detailsSubmitted: true, transfersEnabled: true, payoutsEnabled: true);
    }

    public function totalTransferredTo(string $destination): int
    {
        return (int) collect($this->transfers)->where('destination', $destination)->sum('amount');
    }
}
