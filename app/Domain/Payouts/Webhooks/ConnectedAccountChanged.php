<?php

namespace App\Domain\Payouts\Webhooks;

use App\Domain\Payments\Models\WebhookEvent;
use App\Domain\Payments\Webhooks\WebhookHandler;
use App\Domain\Payouts\Actions\SyncConnectedAccount;
use App\Domain\Suppliers\Models\Supplier;

/**
 * v2.core.account.updated / v2.core.account[...].capability_status_updated
 *
 * Accounts v2 events are "thin": they only say WHICH account changed
 * (related_object.id), not its new state. So we fetch the account from Stripe
 * and sync it — which also releases any transfers that were waiting for it.
 */
class ConnectedAccountChanged implements WebhookHandler
{
    public function __construct(private SyncConnectedAccount $sync) {}

    public function handle(WebhookEvent $event): void
    {
        $accountId = $event->payload['related_object']['id'] ?? null;
        $supplier = $accountId ? Supplier::where('stripe_account_id', $accountId)->first() : null;

        if ($supplier) {
            $this->sync->handle($supplier); // no status passed → retrieved fresh from Stripe
        }
    }
}
