<?php

namespace App\Domain\Payments\Webhooks;

use Stripe\Exception\SignatureVerificationException;
use Stripe\WebhookSignature;

/**
 * Proves a webhook really came from Stripe.
 *
 * Stripe signs every webhook with a secret only Stripe and we know (whsec_...).
 * The signature covers the exact raw body plus a timestamp, so a forged or
 * replayed-later request is rejected before we act on it.
 *
 * We verify the signature ourselves and decode the JSON, instead of using
 * Webhook::constructEvent(), because that helper rejects the "thin" v2 events
 * Stripe sends for Accounts v2 (v2.core.account.updated, ...). Same check, both formats.
 */
class StripeSignatureVerifier
{
    public function __construct(private ?string $secret) {}

    /**
     * @return array the decoded event (snapshot event or thin v2 event)
     *
     * @throws InvalidWebhookSignature
     */
    public function verify(string $payload, ?string $signatureHeader): array
    {
        if (! $this->secret) {
            throw new InvalidWebhookSignature('STRIPE_WEBHOOK_SECRET is not configured.');
        }

        try {
            WebhookSignature::verifyHeader($payload, (string) $signatureHeader, $this->secret, 300);
        } catch (SignatureVerificationException $e) {
            throw new InvalidWebhookSignature($e->getMessage(), previous: $e);
        }

        $event = json_decode($payload, true);

        if (! is_array($event) || ! isset($event['id'], $event['type'])) {
            throw new InvalidWebhookSignature('The payload is not a Stripe event.');
        }

        return $event;
    }
}
