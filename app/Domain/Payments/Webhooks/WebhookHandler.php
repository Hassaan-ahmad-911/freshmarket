<?php

namespace App\Domain\Payments\Webhooks;

use App\Domain\Payments\Models\WebhookEvent;

/** One class per Stripe event type we care about. */
interface WebhookHandler
{
    public function handle(WebhookEvent $event): void;
}
