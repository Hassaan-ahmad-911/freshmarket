<?php

namespace App\Domain\Payments\Webhooks\Handlers;

use App\Domain\Payments\Actions\MarkPaymentSucceeded;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\Webhooks\WebhookHandler;
use Illuminate\Support\Facades\Log;

/** payment_intent.succeeded → the order is paid → suppliers get their transfers. */
class PaymentIntentSucceeded implements WebhookHandler
{
    public function __construct(private MarkPaymentSucceeded $markPaid) {}

    public function handle(WebhookEvent $event): void
    {
        $intent = $event->object();
        $orderId = (int) ($intent['metadata']['order_id'] ?? 0);

        if ($orderId === 0) {
            Log::info('payment_intent.succeeded without a FreshMarket order_id — ignored', ['intent' => $intent['id'] ?? null]);

            return;
        }

        $chargeId = $intent['latest_charge'] ?? null;
        $chargeId = is_array($chargeId) ? ($chargeId['id'] ?? null) : $chargeId; // expanded or plain id

        $this->markPaid->handle(
            orderId: $orderId,
            paymentIntentId: $intent['id'],
            chargeId: $chargeId,
            amountReceived: (int) ($intent['amount_received'] ?? $intent['amount'] ?? 0),
        );
    }
}
