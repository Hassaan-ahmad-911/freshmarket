<?php

namespace App\Domain\Payments\Webhooks;

use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Proves a webhook really came from Stripe.
 *
 * Stripe signs every webhook with a secret only Stripe and we know (whsec_...).
 * The signature covers the exact raw body plus a timestamp, so a forged or
 * replayed-later request is rejected before we act on it.
 */
class StripeSignatureVerifier
{
    public function __construct(private ?string $secret) {}

    /**
     * @return array the decoded event
     *
     * @throws InvalidWebhookSignature
     */
    public function verify(string $payload, ?string $signatureHeader): array
    {
        if (! $this->secret) {
            throw new InvalidWebhookSignature('STRIPE_WEBHOOK_SECRET is not configured.');
        }

        try {
            return Webhook::constructEvent($payload, (string) $signatureHeader, $this->secret)->toArray();
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            throw new InvalidWebhookSignature($e->getMessage(), previous: $e);
        }
    }
}
