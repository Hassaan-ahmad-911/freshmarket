<?php

namespace App\Domain\Payments\Webhooks\Handlers;

use App\Domain\Payments\Enums\RefundStatus;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Refund;
use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\Webhooks\WebhookHandler;
use Illuminate\Support\Facades\Log;

/**
 * charge.refunded → Stripe confirms refunded money left the platform.
 *
 * Card refunds usually succeed instantly, but some start as "pending". When the
 * charge's amount_refunded covers everything we asked for, our pending refunds
 * are confirmed. If Stripe reports MORE than we know about, someone refunded
 * from the Stripe Dashboard directly — logged so it can be reconciled.
 */
class ChargeRefunded implements WebhookHandler
{
    public function handle(WebhookEvent $event): void
    {
        $charge = $event->object();

        $payment = Payment::query()
            ->where('charge_id', $charge['id'] ?? '')
            ->when($charge['payment_intent'] ?? null, fn ($q, $pi) => $q->orWhere('payment_intent_id', $pi))
            ->first();

        if ($payment === null) {
            return;
        }

        $amountRefunded = (int) ($charge['amount_refunded'] ?? 0);
        $ours = Refund::where('payment_id', $payment->id)->whereNot('status', RefundStatus::Failed);
        $oursTotal = (int) (clone $ours)->sum('amount');

        if ($amountRefunded >= $oursTotal) {
            (clone $ours)->where('status', RefundStatus::Pending)->update(['status' => RefundStatus::Succeeded]);
        }

        if ($amountRefunded > $oursTotal) {
            Log::warning('Stripe reports refunds FreshMarket did not issue (Dashboard refund?)', [
                'payment' => $payment->id, 'stripe_refunded' => $amountRefunded, 'freshmarket_refunded' => $oursTotal,
            ]);
        }
    }
}
